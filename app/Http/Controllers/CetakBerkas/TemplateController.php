<?php

namespace App\Http\Controllers\CetakBerkas;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\HakAkses;
use App\Models\Menu;
use App\Services\DocumentDataContext;
use App\Services\WordTemplateInspector;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    private function access(string $action = 'lihat'): array
    {
        $menuId = Menu::where('route_name', 'pengaturan-template.index')->value('id');
        $access = HakAkses::where('id_user', auth()->id())->where('id_menu', $menuId)->first();
        abort_unless($access && $access->lihat && $access->{$action}, 403);

        return $access->only(['lihat', 'tambah', 'edit', 'hapus']);
    }

    public function index()
    {
        $permissions = $this->access();
        $search = trim((string) request('q', ''));
        $templates = DocumentTemplate::where('engine', 'docx')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('nama', 'like', '%'.$search.'%')->orWhere('kode', 'like', '%'.$search.'%')))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.cetak_berkas.index', compact('templates', 'permissions'));
    }

    public function create()
    {
        $this->access('tambah');

        return $this->form(new DocumentTemplate(['is_active' => true]));
    }

    public function edit(DocumentTemplate $template)
    {
        $this->access('edit');
        abort_unless($template->engine === 'docx', 404);

        return $this->form($template);
    }

    private function form(DocumentTemplate $template)
    {
        $contextKeys = DocumentDataContext::getContextKeys();
        $inspection = null;
        $inspectionError = null;
        if ($template->file_path) {
            try {
                $inspection = app(WordTemplateInspector::class)->inspect(public_path('document_templates/'.basename($template->file_path)));
            } catch (ValidationException $e) {
                $inspectionError = $e->validator->errors()->first();
            }
        }

        return view('admin.cetak_berkas.form', compact('template', 'contextKeys', 'inspection', 'inspectionError'));
    }

    public function inspect(Request $request)
    {
        $permissions = $this->access();
        abort_unless($permissions['tambah'] || $permissions['edit'], 403);
        $request->validate(['file_template' => ['required', 'file', 'mimes:docx', 'extensions:docx', 'max:5120']]);

        return response()->json(app(WordTemplateInspector::class)->inspect($request->file('file_template')->getRealPath()));
    }

    public function store(Request $request)
    {
        $this->access('tambah');

        return $this->save($request, new DocumentTemplate);
    }

    public function update(Request $request, DocumentTemplate $template)
    {
        $this->access('edit');
        abort_unless($template->engine === 'docx', 404);

        return $this->save($request, $template);
    }

    private function save(Request $request, DocumentTemplate $template)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('document_templates', 'kode')->ignore($template->id)],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'selected_variables' => ['nullable', 'array', 'max:100'],
            'selected_variables.*' => ['string', Rule::in(array_merge(...array_values(DocumentDataContext::getContextKeys())))],
            'file_template' => [$template->exists && $template->file_path ? 'nullable' : 'required', 'file', 'mimes:docx', 'extensions:docx', 'max:5120'],
        ], [
            'file_template.required' => 'Silakan unggah file Word (.docx).',
            'file_template.mimes' => 'File harus berupa dokumen Word (.docx).',
            'file_template.extensions' => 'Gunakan file dengan ekstensi .docx.',
            'file_template.max' => 'Ukuran file maksimal 5 MB.',
            'kode.regex' => 'Kode hanya boleh berisi huruf kecil, angka, dan garis bawah.',
            'kode.unique' => 'Kode template sudah digunakan.',
        ]);
        unset($data['file_template']);
        $path = $request->hasFile('file_template') ? $request->file('file_template')->getRealPath()
            : public_path('document_templates/'.basename($template->file_path));
        $inspection = app(WordTemplateInspector::class)->inspect($path);
        if ($request->boolean('is_active') && $inspection['unknown']) {
            throw ValidationException::withMessages(['file_template' => 'Ada variabel tidak dikenal: '.implode(', ', $inspection['unknown']).'. Perbaiki Word atau simpan dengan status Nonaktif.']);
        }
        $data['detected_variables'] = $inspection['variables'];
        $data['selected_variables'] = array_values(array_unique($data['selected_variables'] ?? []));
        $data['engine'] = 'docx';
        $oldPath = $template->file_path;
        $newPath = null;
        try {
            if ($request->hasFile('file_template')) {
                $newPath = 'template_'.Str::uuid().'.docx';
                $request->file('file_template')->move(public_path('document_templates'), $newPath);
                $data['file_path'] = $newPath;
            }
            DB::transaction(fn () => $template->fill($data)->save());
        } catch (\Throwable $exception) {
            if ($newPath) {
                $this->removeFile($newPath);
            }
            throw $exception;
        }
        if ($newPath && $oldPath && $oldPath !== $newPath) {
            $this->removeFile($oldPath);
        }

        return redirect()->route('pengaturan-template.index')->with('success', 'Template berhasil disimpan.');
    }

    public function download(DocumentTemplate $template)
    {
        $this->access();
        abort_unless($template->engine === 'docx' && $template->file_path, 404);
        $path = public_path('document_templates/'.basename($template->file_path));
        abort_unless(is_file($path), 404, 'File template tidak ditemukan.');

        return response()->download($path, Str::slug($template->nama).'.docx');
    }

    public function destroy(DocumentTemplate $template)
    {
        $this->access('hapus');
        abort_unless($template->engine === 'docx', 404);
        $path = $template->file_path;
        $template->delete();
        $this->removeFile($path);

        return redirect()->route('pengaturan-template.index')->with('success', 'Template berhasil dihapus.');
    }

    private function removeFile(?string $filename): void
    {
        if ($filename && ! DocumentTemplate::where('file_path', $filename)->exists()) {
            $path = public_path('document_templates/'.basename($filename));
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}

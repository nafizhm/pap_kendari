<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DocumentTemplate;
use App\Models\User;
use App\Services\DocumentDataContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CetakBerkasTemplateTest extends TestCase
{
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        (require database_path('migrations/2026_07_05_000001_create_document_templates_table.php'))->up();
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
        });
        Schema::create('hak_akses', function (Blueprint $table) {
            $table->id();
            $table->integer('id_menu');
            $table->integer('id_user');
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $action) $table->boolean($action);
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'pengaturan-template.index']);
        DB::table('hak_akses')->insert(['id_menu' => 1, 'id_user' => 1, 'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1]);
        $user = new User;
        $user->id = 1;
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $path) {
            if (is_file($path)) unlink($path);
        }
        parent::tearDown();
    }

    private function payload(): array
    {
        return ['nama' => 'Surat Customer', 'kode' => 'surat_customer', 'deskripsi' => 'Surat percobaan', 'is_active' => '1'];
    }

    private function word(): UploadedFile
    {
        return UploadedFile::fake()->create('template.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    public function test_word_template_can_be_created_updated_downloaded_replaced_and_deleted(): void
    {
        $this->post(route('pengaturan-template.store'), $this->payload() + ['file_template' => $this->word()])->assertRedirect(route('pengaturan-template.index'));
        $template = DocumentTemplate::firstOrFail();
        $this->files[] = $oldPath = public_path('document_templates/'.$template->file_path);
        $this->assertFileExists($oldPath);
        $this->get(route('pengaturan-template.download', $template))->assertDownload('surat-customer.docx');
        $this->put(route('pengaturan-template.update', $template), array_replace($this->payload(), ['nama' => 'Surat Baru', 'is_active' => '0']))->assertRedirect();
        $this->assertSame($oldPath, public_path('document_templates/'.$template->fresh()->file_path));
        $this->assertFalse($template->fresh()->is_active);
        $this->put(route('pengaturan-template.update', $template), $this->payload() + ['file_template' => $this->word()])->assertRedirect();
        $this->files[] = $newPath = public_path('document_templates/'.$template->fresh()->file_path);
        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists($newPath);
        $this->delete(route('pengaturan-template.destroy', $template))->assertRedirect();
        $this->assertDatabaseMissing('document_templates', ['id' => $template->id]);
        $this->assertFileDoesNotExist($newPath);
    }

    public function test_upload_is_required_and_rejects_non_word_and_oversized_files(): void
    {
        $this->post(route('pengaturan-template.store'), $this->payload())->assertSessionHasErrors('file_template');
        $this->post(route('pengaturan-template.store'), $this->payload() + ['file_template' => UploadedFile::fake()->create('file.pdf', 10, 'application/pdf')])->assertSessionHasErrors('file_template');
        $this->post(route('pengaturan-template.store'), $this->payload() + ['file_template' => $this->word()->size(5121)])->assertSessionHasErrors('file_template');
        $this->assertDatabaseCount('document_templates', 0);
    }

    public function test_read_only_user_cannot_modify_templates(): void
    {
        DB::table('hak_akses')->update(['tambah' => 0, 'edit' => 0, 'hapus' => 0]);
        $template = DocumentTemplate::create($this->payload() + ['engine' => 'docx']);
        $this->post(route('pengaturan-template.store'), $this->payload())->assertForbidden();
        $this->put(route('pengaturan-template.update', $template), $this->payload())->assertForbidden();
        $this->delete(route('pengaturan-template.destroy', $template))->assertForbidden();
        $this->assertDatabaseHas('document_templates', ['id' => $template->id]);
    }

    public function test_customer_name_variable_resolves_and_all_advertised_variables_have_values(): void
    {
        $customer = new Customer(['nama_lengkap' => 'Budi']);
        $customer->setRelation('kavlingPeta', null);
        $customer->setRelation('lokasiKavling', null);
        $context = DocumentDataContext::getAllForCustomer($customer);
        $this->assertSame('Budi', $context['nama_customer']);
        foreach (DocumentDataContext::getContextKeys() as $keys) {
            foreach ($keys as $key) $this->assertArrayHasKey($key, $context);
        }
    }
}

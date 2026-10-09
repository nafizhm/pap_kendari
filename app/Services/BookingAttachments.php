<?php

namespace App\Services;

use App\Models\BerkasBooking;
use App\Models\PengajuanHold;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BookingAttachments
{
    public function definitions(?PengajuanHold $booking = null): Collection
    {
        return BerkasBooking::withTrashed()->orderBy('urutan')->orderBy('id')->get()
            ->filter(fn ($type) => ($type->aktif && ! $type->trashed()) || $type->filename($booking));
    }

    public function rules(?PengajuanHold $booking = null, bool $requireMandatory = true): array
    {
        $rules = ['berkas_booking_files' => 'nullable|array'];
        $allowed = [];
        foreach ($this->definitions($booking) as $type) {
            $enabled = $type->aktif && ! $type->trashed();
            $required = $requireMandatory && $enabled && $type->wajib && ! $type->filename($booking);
            $rules[$type->inputKey()] = ($required ? 'required' : 'nullable') . '|file|mimes:jpg,jpeg,png,webp,pdf|max:10240';
            if (! array_key_exists($type->kode, BerkasBooking::LEGACY)) {
                $allowed[] = $type->kode;
            }
        }
        // Reject uploads for nonexistent or hidden definitions.
        $rules['berkas_booking_files'] .= $allowed ? '|array:' . implode(',', $allowed) : '|size:0';
        foreach (BerkasBooking::LEGACY as $field => $label) {
            $rules[$field] ??= 'prohibited';
        }
        return $rules;
    }

    public function attributes(?PengajuanHold $booking = null): array
    {
        return $this->definitions($booking)->mapWithKeys(fn ($type) => [$type->inputKey() => $type->nama])->all();
    }

    public function save(Request $request, ?PengajuanHold $booking, array &$newFiles, array &$obsoleteFiles): array
    {
        $values = [];
        $custom = $booking?->berkas_booking ?? [];
        foreach ($this->definitions($booking) as $type) {
            if (! $request->hasFile($type->inputKey())) {
                continue;
            }
            File::ensureDirectoryExists(public_path('assets/booking'));
            $upload = $request->file($type->inputKey());
            $filename = (string) Str::uuid() . '.' . $upload->extension();
            $newFiles[] = $filename;
            $upload->move(public_path('assets/booking'), $filename);
            if ($old = $type->filename($booking)) {
                $obsoleteFiles[] = public_path('assets/booking/' . $old);
            }
            if (array_key_exists($type->kode, BerkasBooking::LEGACY)) {
                $values[$type->kode] = $filename;
            } else {
                $custom[$type->kode] = ['nama' => $type->nama, 'file' => $filename];
            }
        }
        $values['berkas_booking'] = $custom;
        return $values;
    }

    public function files(PengajuanHold $booking): array
    {
        $files = [];
        foreach (BerkasBooking::withTrashed()->get() as $type) {
            if ($filename = $type->filename($booking)) {
                $files[$type->kode] = ['nama' => $type->nama, 'file' => $filename];
            }
        }
        return array_merge($booking->berkas_booking ?? [], $files);
    }
}

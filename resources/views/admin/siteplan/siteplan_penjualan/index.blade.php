<!-- resources/views/admin/bank/index.blade.php -->
@extends('admin.layout_admin')
@section('content')
    <style>
        .legend {
            position: fixed;
            top: 80px;
            right: 30px;
            padding: 10px;
            font-size: 14px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.4);
            transition: right 0.3s ease;
            width: 200px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 5px;
            border-radius: 50%;
            border: 2px solid #000;
        }

        .toggle-btn {
            width: 100%;
            padding: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            margin-top: 10px;
        }

        .show-btn {
            position: fixed;
            top: 100px;
            right: 30px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.4);
            cursor: pointer;
            display: none;
        }

        .svg-container {
            width: 100%;
            height: 100vh;
            overflow: hidden;
            border: 2px solid #d1cfcf;
            position: relative;
        }

        .svg-container svg {
            width: 100%;
            height: 100%;
            cursor: grab;
            transition: transform 0.1s ease-out;
        }
    </style>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 font-weight-bold text-lg text-dark">Siteplan</h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-12">

                        <!-- ================================================================================================== -->

                        <div class="card card-primary card-outline card-outline-tabs">
                            <div class="card-header p-0 border-bottom-0">
                                <ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
                                    @foreach ($lokasiKavling as $index => $kav)
                                        <li class="nav-item">
                                            <a class="nav-link {{ $index == 0 ? 'active' : '' }}"
                                                id="custom-tabs-four-{{ $kav->id }}-tab" data-toggle="pill"
                                                href="#custom-tabs-four-{{ $kav->id }}" role="tab">
                                                {{ $kav->nama_kavling }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="card-body">
                                <div class="tab-content" id="custom-tabs-four-tabContent">
                                    @foreach ($lokasiKavling as $index => $kav)
                                        <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}"
                                            id="custom-tabs-four-{{ $kav->id }}" role="tabpanel">

                                            @if ($kav->masterSvg)
                                            <a href="{{ route('siteplan-penjualan.cetak.pdf', $kav->id) }}" target="_blank"
                                                class="btn btn-danger btn-sm mr-1">
                                                <i class="fas fa-file-pdf mr-1"></i> Cetak Denah PDF
                                            </a>
                                            <a href="{{ route('siteplan-penjualan.cetak.jpg', $kav->id) }}" target="_blank"
                                                class="btn btn-primary btn-sm">
                                                <i class="fas fa-file-image mr-1"></i> Download Denah JPG
                                            </a>

                                            {{-- SVG Container khusus lokasi ini --}}
                                            <div class="svg-container mt-3">
                                                <button class="reset-button btn btn-success btn-sm"
                                                    style="position: absolute; top: 10px; left: 10px; z-index: 10;">
                                                    Reset Siteplan
                                                </button>

                                                {{-- SVG Header --}}
                                                @if ($kav->masterSvg)
                                                    {!! str_replace(['[[lebar]]', '[[tinggi]]'], ['100%', '100%'], $kav->masterSvg->header_svg) !!}
                                                @endif

                                                {{-- Loop kavling --}}
                                                @foreach ($kav->kavlingPeta as $pt)
                                                    @php
                                                        $warna = '#ffffff';

                                                        if ($pt->customer) {
                                                            $warna = $pt->customer->progres->warna ?? '#ffffff';
                                                        } else {
                                                            if ($pt->is_booked) {
                                                                $warna = '#42f202';
                                                            }
                                                        }
                                                    @endphp


                                                    @if ($pt->jenis_map == 'polygon')
                                                        <a href="javascript:void(0);" class="detail-button"
                                                            data-id="{{ $pt->id }}"
                                                            data-url="{{ route('siteplan-penjualan.show', $pt->id) }}">
                                                            {!! str_replace(
                                                                ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                                                                [$pt->map, $warna, $pt->matrik, $pt->kode_kavling],
                                                                $kav->masterSvg->polygon_svg,
                                                            ) !!}
                                                        </a>
                                                    @elseif ($pt->jenis_map == 'path')
                                                        <a href="javascript:void(0);" class="detail-button"
                                                            data-id="{{ $pt->id }}"
                                                            data-url="{{ route('siteplan-penjualan.show', $pt->id) }}">
                                                            {!! str_replace(
                                                                ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                                                                [$pt->map, $warna, $pt->matrik, $pt->kode_kavling],
                                                                $kav->masterSvg->path_svg,
                                                            ) !!}
                                                        </a>
                                                    @endif
                                                @endforeach

                                                {{-- SVG Footer --}}
                                                @if ($kav->masterSvg)
                                                    {!! $kav->masterSvg->footer_svg !!}
                                                @endif
                                            </div>
                                            @else
                                                <div class="alert alert-info mt-3" role="status">
                                                    Siteplan {{ $kav->nama_kavling }} belum tersedia. Silakan lengkapi template denah lokasi ini.
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- /.card -->
                        </div>
                    </div>

                    <!-- ========================================================================================== -->

                    <!-- Tombol show di luar legenda -->
                    <button class="show-btn btn-xs" id="show-btn" onclick="toggleLegend()">Show</button>

                    <!-- Tambahkan legenda -->
                    <div class="legend" id="legend">
                        @foreach ($legend as $item)
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: {{ $item->warna }}"></div>
                                {{ $item->status_progres }}
                            </div>
                        @endforeach

                        <!-- Tombol hide -->
                        <button class="toggle-btn" onclick="toggleLegend()">Hide</button>
                    </div>
                    <!-- ========================================================================================== -->

                </div>
            </div>
        </section>
    </div><!-- /.container-fluid -->
    <!-- /.content-wrapper -->

    @include('admin.siteplan.siteplan_penjualan.detail')
@endsection

@push('scripts')
    <script src="{{ asset('assets/svg_1.js') }}"></script>
@endpush

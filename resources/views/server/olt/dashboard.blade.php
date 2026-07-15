@extends('server.olt.layout')

@section('content')
<!-- ============================================================== -->
<!-- Start right Content here -->
<!-- ============================================================== -->

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-xl-12 stretch-card">
                <div class="row flex-grow-1">
                    @if (session('auth_errors'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-close-circle me-2" aria-hidden="true"></i>
                            @foreach (session('auth_errors') as $err)
                                {{ $err }}
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                            @foreach (session('success') as $suc)
                                {{ $suc }}
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                        </div>
                    @endif
                    <div class="col-md-12 grid-margin stretch-card">

                        <div class="card bg-primary text-white">
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-md-6">
                                        Welcome back {{ auth()->user()->nama }} !
                                        <p>OLT HSGQ Web Based For Network Technician.</p>
                                        <ul>
                                            <li>Feature Simple </li>
                                            <li>Template Responsive </li>
                                            <li>Display is almost similar to that of the HSGQ UI </li>
                                        </ul>
                                        <a href="#" class="btn btn-warning m-t-xs">Learn More</a>

                                    </div>
                                    <div class="info-image col-md-6"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-12 stretch-card">
                <div class="card">
                    <div class="card-body">
                        @if (! ($dataLoaded ?? true))
                            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span><i class="mdi mdi-information-outline me-2"></i> Data dashboard OLT belum dimuat. Klik tombol untuk mengambil data dari OLT.</span>
                                <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">
                                    <i class="mdi mdi-download"></i> Tampilkan Data
                                </a>
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">PON</th>
                                        <th scope="col">Online</th>
                                        <th scope="col">Offline</th>
                                        <th scope="col">Total</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">#</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($onu as $row)
                                        @php
                                            if ($row['status'] == '1') {
                                                $statusnya = 'Online';
                                                $badge = 'success';
                                            } else {
                                                $statusnya = 'Offline';
                                                $badge = 'danger';
                                            }
                                            $total = $row['online'] + $row['offline'];
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>PON {{ $row['port_id'] }} </td>
                                            <td>Online : {{ $row['online'] }} ONU</td>
                                            <td>Offline : {{ $row['offline'] }} ONU</td>
                                            <td>Total : {{ $total }} ONU</td>

                                            <td><span class="btn btn-sm btn-{{ $badge }}">{{ $statusnya }}</span></td>
                                            <td><a href="{{ url('server/olt/pon/' . $row['port_id']) }}" class="btn btn-xs btn-primary"> <i class="mdi mdi-arrow-right"></i> Lihat Data
                                                </a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

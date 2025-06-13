@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0 fw-bold">
                                    <i class="fas fa-file-invoice text-primary me-2"></i>
                                    Invoice #{{ $invoice->InvNumber }}
                                </h3>
                                <p class="text-muted mb-0">Detailed view and certification status</p>
                            </div>
                            <div class="btn-group">
                                @if (!$invoice->isCertified())
                                    <button type="button" class="btn btn-primary certify-btn"
                                        data-invoice-id="{{ $invoice->id }}">
                                        <i class="fas fa-certificate me-1"></i> Certify
                                    </button>
                                @endif
                                @if ($invoice->isCertified())
                                    <a href="{{ route('fne.invoices.pdf', $invoice) }}"
                                        class="btn btn-success btn-block mb-3" target="_blank">
                                        <i class="fas fa-file-pdf"></i> Download PDF
                                    </a>
                                @endif
                                <a href="{{ route('fne.invoices.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Invoice Information -->
                            <div class="col-lg-6">
                                <div class="card border-0 shadow-sm rounded-3 h-100">
                                    <div class="card-header bg-white border-bottom-0 py-3">
                                        <h5 class="mb-0 fw-bold">
                                            <i class="fas fa-info-circle text-primary me-2"></i>
                                            Invoice Information
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-borderless mb-0">
                                                <tbody>
                                                    <tr>
                                                        <th width="35%" class="text-muted small text-uppercase fw-bold">
                                                            Invoice Number</th>
                                                        <td class="fw-bold">{{ $invoice->InvNumber }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-muted small text-uppercase fw-bold">Date</th>
                                                        <td>{{ $invoice->InvDate ? $invoice->InvDate->format('M d, Y') : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-muted small text-uppercase fw-bold">Customer</th>
                                                        <td>{{ $invoice->cAccountName ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-muted small text-uppercase fw-bold">Tax Number (NCC)
                                                        </th>
                                                        <td>{{ $invoice->cTaxNumber ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-muted small text-uppercase fw-bold">Amount</th>
                                                        <td>
                                                            <div class="d-flex align-items-baseline">
                                                                <span
                                                                    class="fw-bold fs-5">{{ number_format($invoice->InvTotIncl, 0) }}</span>
                                                                <span class="ms-2 text-muted small">XOF</span>
                                                            </div>
                                                            <div class="text-muted small mt-1">
                                                                HT: {{ number_format($invoice->InvTotExcl, 0) }} |
                                                                TVA: {{ number_format($invoice->InvTotTax, 0) }}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-muted small text-uppercase fw-bold">Description</th>
                                                        <td>{{ $invoice->Description ?? 'N/A' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Certification Status -->
                            <div class="col-lg-6">
                                <div class="card border-0 shadow-sm rounded-3 h-100">
                                    <div class="card-header bg-white border-bottom-0 py-3">
                                        <h5 class="mb-0 fw-bold">
                                            <i class="fas fa-shield-alt text-primary me-2"></i>
                                            FNE Certification
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @if ($invoice->fneCertification)
                                            <div class="table-responsive">
                                                <table class="table table-borderless mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <th width="35%"
                                                                class="text-muted small text-uppercase fw-bold">Status</th>
                                                            <td>
                                                                @switch($invoice->fneCertification->certification_status)
                                                                    @case('certified')
                                                                        <span
                                                                            class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1">
                                                                            <i class="fas fa-check-circle me-1"></i> Certified
                                                                        </span>
                                                                    @break

                                                                    @case('pending')
                                                                        <span
                                                                            class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1">
                                                                            <i class="fas fa-clock me-1"></i> Pending
                                                                        </span>
                                                                    @break

                                                                    @case('failed')
                                                                        <span
                                                                            class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1">
                                                                            <i class="fas fa-exclamation-triangle me-1"></i> Failed
                                                                        </span>
                                                                    @break

                                                                    @default
                                                                        <span
                                                                            class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1">
                                                                            <i class="fas fa-question-circle me-1"></i> Unknown
                                                                        </span>
                                                                @endswitch
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted small text-uppercase fw-bold">FNE
                                                                Reference</th>
                                                            <td>
                                                                @if ($invoice->fneCertification->fne_reference)
                                                                    <code
                                                                        class="text-primary">{{ $invoice->fneCertification->fne_reference }}</code>
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted small text-uppercase fw-bold">Certified At
                                                            </th>
                                                            <td>
                                                                @if ($invoice->fneCertification->certified_at)
                                                                    {{ $invoice->fneCertification->certified_at->format('M d, Y H:i') }}
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted small text-uppercase fw-bold">QR Code</th>
                                                            <td>
                                                                @if ($invoice->fneCertification->fne_qr_url)
                                                                    <a href="{{ $invoice->fneCertification->fne_qr_url }}"
                                                                        target="_blank"
                                                                        class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-qrcode me-1"></i> View QR Code
                                                                    </a>
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted small text-uppercase fw-bold">Sticker
                                                                Balance</th>
                                                            <td>{{ $invoice->fneCertification->balance_sticker ?? 'N/A' }}
                                                            </td>
                                                        </tr>
                                                        @if ($invoice->fneCertification->error_message)
                                                            <tr>
                                                                <th class="text-muted small text-uppercase fw-bold">Error
                                                                    Message</th>
                                                                <td class="text-danger small">
                                                                    {{ $invoice->fneCertification->error_message }}</td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>

                                            @if ($invoice->fneCertification->certification_status === 'failed')
                                                <div class="mt-4 text-end">
                                                    <button type="button" class="btn btn-warning retry-btn"
                                                        data-invoice-id="{{ $invoice->id }}">
                                                        <i class="fas fa-redo me-1"></i> Retry Certification
                                                    </button>
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-center py-4">
                                                <div class="mb-4">
                                                    <i class="fas fa-shield-alt fa-4x text-muted opacity-25"></i>
                                                </div>
                                                <h5 class="text-muted">This invoice has not been certified yet</h5>
                                                <p class="text-muted mb-4">Click the certify button to begin the
                                                    certification process</p>
                                                <button type="button" class="btn btn-primary certify-btn px-4"
                                                    data-invoice-id="{{ $invoice->id }}">
                                                    <i class="fas fa-certificate me-2"></i> Certify Now
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($invoice->fneCertification)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm rounded-3">
                                        <div class="card-header bg-white border-bottom-0 py-3">
                                            <h5 class="mb-0 fw-bold">
                                                <i class="fas fa-code text-primary me-2"></i>
                                                API Communication
                                            </h5>
                                        </div>
                                        <div class="card-body p-0">
                                            <ul class="nav nav-tabs nav-tabs-card" id="apiDataTabs" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active px-4 py-3" id="request-tab"
                                                        data-bs-toggle="tab" data-bs-target="#request" type="button">
                                                        <i class="fas fa-paper-plane me-2"></i> Request Payload
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link px-4 py-3" id="response-tab"
                                                        data-bs-toggle="tab" data-bs-target="#response" type="button">
                                                        <i class="fas fa-reply me-2"></i> Response Payload
                                                    </button>
                                                </li>
                                            </ul>
                                            <div class="tab-content border border-top-0 rounded-bottom">
                                                <div class="tab-pane fade show active p-3 bg-light" id="request"
                                                    role="tabpanel">
                                                    <div class="bg-white p-3 rounded border">
                                                        <pre class="mb-0 p-3 bg-dark text-white rounded"><code>@json($invoice->fneCertification->request_payload, JSON_PRETTY_PRINT)</code></pre>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade p-3 bg-light" id="response" role="tabpanel">
                                                    <div class="bg-white p-3 rounded border">
                                                        <pre class="mb-0 p-3 bg-dark text-white rounded"><code>@json($invoice->fneCertification->response_payload, JSON_PRETTY_PRINT)</code></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .nav-tabs-card .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            border-radius: 0;
            border-bottom: 3px solid transparent;
        }

        .nav-tabs-card .nav-link.active {
            color: #0d6efd;
            background: transparent;
            border-bottom: 3px solid #0d6efd;
        }

        .nav-tabs-card {
            border-bottom: 1px solid #e9ecef;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.875em;
        }

        code {
            color: #e83e8c;
            word-wrap: break-word;
        }

        .table-borderless tbody tr:not(:last-child) {
            border-bottom: 1px solid #f0f0f0;
        }

        .table-borderless tbody tr {
            padding: 8px 10px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Handle certification button with SweetAlert
            $('.certify-btn').click(function() {
                const invoiceId = $(this).data('invoice-id');

                Swal.fire({
                    title: 'Confirm Certification',
                    html: `<div class="text-center">
                            <i class="fas fa-certificate fa-3x text-primary mb-3"></i>
                            <p>Are you sure you want to certify this invoice with FNE?</p>
                        </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, certify it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-primary px-4',
                        cancelButton: 'btn btn-outline-secondary px-4'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        certifyInvoice(invoiceId);
                    }
                });
            });

            // Handle retry button with SweetAlert
            $('.retry-btn').click(function() {
                const invoiceId = $(this).data('invoice-id');

                Swal.fire({
                    title: 'Confirm Retry',
                    html: `<div class="text-center">
                            <i class="fas fa-redo fa-3x text-warning mb-3"></i>
                            <p>Are you sure you want to retry certification for this invoice?</p>
                        </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, retry it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-warning px-4',
                        cancelButton: 'btn btn-outline-secondary px-4'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        retryCertification(invoiceId);
                    }
                });
            });

            function certifyInvoice(invoiceId) {
                $.ajax({
                    url: `/fne/invoices/${invoiceId}/certify`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Processing',
                            html: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i><p>Certifying invoice...</p></div>',
                            allowOutsideClick: false,
                            showConfirmButton: false
                        });
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                html: '<div class="text-center"><i class="fas fa-check-circle fa-3x text-success mb-3"></i><p>Invoice certified successfully!</p></div>',
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-success px-4'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                html: `<div class="text-center"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><p>${response.message}</p></div>`,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-danger px-4'
                                },
                                buttonsStyling: false
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error!',
                            html: `<div class="text-center"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><p>An error occurred during certification.</p></div>`,
                            showConfirmButton: true,
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-danger px-4'
                            },
                            buttonsStyling: false
                        });
                        console.error(xhr.responseText);
                    }
                });
            }

            function retryCertification(invoiceId) {
                $.ajax({
                    url: `/fne/invoices/${invoiceId}/retry`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Processing',
                            html: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i><p>Retrying certification...</p></div>',
                            allowOutsideClick: false,
                            showConfirmButton: false
                        });
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                html: '<div class="text-center"><i class="fas fa-check-circle fa-3x text-success mb-3"></i><p>Certification retry successful!</p></div>',
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-success px-4'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                html: `<div class="text-center"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><p>${response.message}</p></div>`,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-danger px-4'
                                },
                                buttonsStyling: false
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error!',
                            html: `<div class="text-center"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><p>An error occurred during retry.</p></div>`,
                            showConfirmButton: true,
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-danger px-4'
                            },
                            buttonsStyling: false
                        });
                        console.error(xhr.responseText);
                    }
                });
            }
        });
    </script>
@endpush

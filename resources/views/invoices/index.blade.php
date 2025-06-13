@extends('layouts.app')

@section('title', 'FNE Invoice Certification')

@section('content')
    <div class="container-fluid px-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0 fw-bold">
                                    <i class="fas fa-file-invoice-dollar text-primary me-2"></i>Invoice Certification
                                </h4>
                                <p class="text-muted mb-0">Manage and certify your invoices with FNE</p>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary" id="bulkCertifyBtn" disabled>
                                    <i class="fas fa-certificate me-1"></i> Certify Selected
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh
                                </button>
                                {{-- <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#syncModal">
                                    <i class="fas fa-cloud-download-alt me-1"></i> Sync
                                </a> --}}
                            </div>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="card-body border-bottom bg-light">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small text-uppercase fw-bold text-muted">Invoice Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control"
                                        value="{{ request('search') }}" placeholder="Invoice number...">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-uppercase fw-bold text-muted">Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    <option value="certified" {{ request('status') === 'certified' ? 'selected' : '' }}>
                                        Certified
                                    </option>
                                    <option value="uncertified" {{ request('status') === 'uncertified' ? 'selected' : '' }}>
                                        Uncertified
                                    </option>
                                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>
                                        Failed
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small text-uppercase fw-bold text-muted">Date Range</label>
                                <div class="input-daterange input-group">
                                    <input type="date" name="from_date" class="form-control form-control-sm"
                                        value="{{ request('from_date') }}" placeholder="From">
                                    <span class="input-group-text bg-white">to</span>
                                    <input type="date" name="to_date" class="form-control form-control-sm"
                                        value="{{ request('to_date') }}" placeholder="To">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-filter me-1"></i> Apply
                                </button>
                                <a href="{{ route('fne.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Clear
                                </a>
                            </div>

                            <div class="col-md-2 text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <span class="badge bg-success rounded-pill">
                                        {{ $invoices->where('fneCertification.certification_status', 'certified')->count() }}
                                        Certified
                                    </span>
                                    <span class="badge bg-warning rounded-pill">
                                        {{ $invoices->where('fneCertification.certification_status', 'pending')->count() }}
                                        Pending
                                    </span>
                                    <span class="badge bg-danger rounded-pill">
                                        {{ $invoices->where('fneCertification.certification_status', 'failed')->count() }}
                                        Failed
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="invoicesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40" class="py-3">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th class="py-3">Invoice #</th>
                                        <th class="py-3">Date</th>
                                        <th class="py-3">Customer</th>
                                        <th class="py-3">Amount</th>
                                        <th class="py-3">Status</th>
                                        <th class="py-3">FNE Reference</th>
                                        <th class="py-3 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $invoice)
                                        <tr
                                            class="{{ $invoice->fneCertification && $invoice->fneCertification->certification_status === 'certified' ? 'bg-light' : '' }}">
                                            <td class="align-middle">
                                                @if (!$invoice->fneCertification || $invoice->fneCertification->certification_status !== 'certified')
                                                    <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}"
                                                        class="form-check-input invoice-checkbox">
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <div class="fw-bold">{{ $invoice->InvNumber }}</div>
                                                <small class="text-muted">#{{ $invoice->AutoIndex }}</small>
                                            </td>
                                            <td class="align-middle">
                                                {{ $invoice->InvDate ? $invoice->InvDate->format('M d, Y') : 'N/A' }}
                                            </td>
                                            <td class="align-middle">
                                                <div class="text-truncate" style="max-width: 200px;"
                                                    title="{{ $invoice->cAccountName ?? 'N/A' }}">
                                                    {{ $invoice->cAccountName ?? 'N/A' }}
                                                </div>
                                                @if ($invoice->cTaxNumber)
                                                    <small class="text-muted">NCC: {{ $invoice->cTaxNumber }}</small>
                                                @endif
                                            </td>
                                            <td class="align-middle fw-bold">
                                                {{ number_format($invoice->InvTotIncl, 0) }} XOF
                                            </td>
                                            <td class="align-middle">
                                                @if ($invoice->fneCertification)
                                                    @switch($invoice->fneCertification->certification_status)
                                                        @case('certified')
                                                            <span
                                                                class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1">
                                                                <i class="fas fa-check-circle me-1"></i> Certified
                                                            </span>
                                                            @if ($invoice->fneCertification->certified_at)
                                                                <div class="small text-muted mt-1">
                                                                    {{ $invoice->fneCertification->certified_at->format('M d, Y H:i') }}
                                                                </div>
                                                            @endif
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
                                                            @if ($invoice->fneCertification->error_message)
                                                                <div class="small text-danger mt-1">
                                                                    {{ Str::limit($invoice->fneCertification->error_message, 30) }}
                                                                </div>
                                                            @endif
                                                        @break
                                                    @endswitch
                                                @else
                                                    <span
                                                        class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1">
                                                        <i class="fas fa-minus me-1"></i> Not Processed
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                @if ($invoice->fneCertification && $invoice->fneCertification->fne_reference)
                                                    <code
                                                        class="text-primary">{{ $invoice->fneCertification->fne_reference }}</code>
                                                    @if ($invoice->fneCertification->fne_qr_url)
                                                        <div class="mt-1">
                                                            <a href="{{ $invoice->fneCertification->fne_qr_url }}"
                                                                target="_blank"
                                                                class="btn btn-xs btn-outline-primary btn-sm">
                                                                <i class="fas fa-qrcode me-1"></i> QR
                                                            </a>
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-end">
                                                <div class="btn-group">
                                                    <a href="{{ route('fne.invoices.show', $invoice) }}"
                                                        class="btn btn-sm btn-outline-secondary rounded-start"
                                                        title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    @if (!$invoice->fneCertification || $invoice->fneCertification->certification_status !== 'certified')
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary certify-btn"
                                                            data-invoice-id="{{ $invoice->id }}"
                                                            data-invoice-number="{{ $invoice->InvNumber }}"
                                                            title="Certify Invoice">
                                                            <i class="fas fa-certificate"></i>
                                                        </button>
                                                    @endif

                                                    @if ($invoice->fneCertification && $invoice->fneCertification->certification_status === 'failed')
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-warning rounded-end retry-btn"
                                                            data-invoice-id="{{ $invoice->id }}"
                                                            data-invoice-number="{{ $invoice->InvNumber }}"
                                                            title="Retry Certification">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary rounded-end" disabled>
                                                            <i class="fas fa-ellipsis-h"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <div class="py-5">
                                                        <i class="fas fa-file-invoice fa-3x text-muted mb-4"></i>
                                                        <h5 class="text-muted">No invoices found</h5>
                                                        <p class="text-muted">Try adjusting your search or filter criteria</p>
                                                        <a href="{{ route('fne.invoices.index') }}"
                                                            class="btn btn-sm btn-outline-primary mt-2">
                                                            <i class="fas fa-sync me-1"></i> Reset Filters
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($invoices->hasPages())
                                <div class="card-footer bg-white border-top py-3">
                                    {{ $invoices->appends(request()->query())->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sync Invoices Modal -->
            <div class="modal fade" id="syncModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title">
                                <i class="fas fa-cloud-download-alt text-primary me-2"></i>Sync Invoices
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="syncForm" action="{{ route('fne.invoices.sync') }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-4">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Date Range</label>
                                    <div class="input-daterange input-group">
                                        <input type="date" class="form-control" name="sync_from_date"
                                            placeholder="Start date">
                                        <span class="input-group-text bg-light">to</span>
                                        <input type="date" class="form-control" name="sync_to_date"
                                            placeholder="End date">
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="forceResync" name="force_resync">
                                    <label class="form-check-label" for="forceResync">
                                        Force resync of certified invoices
                                    </label>
                                </div>
                                <div class="alert alert-light border">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    This will fetch invoices from your database and check their certification status with FNE.
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sync me-1"></i> Start Sync
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @section('styles')
        <style>
            .card {
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            }

            .card-header {
                border-radius: 12px 12px 0 0 !important;
            }

            .table {
                margin-bottom: 0;
            }

            .table th {
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.5px;
                color: #6c757d;
            }

            .badge {
                font-weight: 500;
            }

            .btn-group .btn {
                border-radius: 0;
            }

            .btn-group .btn:first-child {
                border-top-left-radius: 6px;
                border-bottom-left-radius: 6px;
            }

            .btn-group .btn:last-child {
                border-top-right-radius: 6px;
                border-bottom-right-radius: 6px;
            }

            .form-control,
            .form-select {
                border-radius: 8px;
            }

            .pagination {
                justify-content: center;
            }
        </style>
    @endsection

    @section('scripts')
        <script>
            $(document).ready(function() {
                // Initialize DataTable with search functionality
                $('#invoicesTable').DataTable({
                    paging: false,
                    searching: false,
                    info: false,
                    order: [
                        [2, 'desc']
                    ], // Sort by date descending
                    columnDefs: [{
                            orderable: false,
                            targets: [0, 7]
                        } // Disable sorting for checkbox and actions columns
                    ],
                    dom: '<"top"f>rt<"bottom"lip><"clear">',
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search invoices...",
                    }
                });

                // Select/Deselect all checkboxes
                $('#selectAll').change(function() {
                    $('.invoice-checkbox').prop('checked', $(this).prop('checked'));
                    toggleBulkCertifyBtn();
                });

                // Toggle bulk certify button based on selected checkboxes
                $('.invoice-checkbox').change(function() {
                    toggleBulkCertifyBtn();
                });

                function toggleBulkCertifyBtn() {
                    const anyChecked = $('.invoice-checkbox:checked').length > 0;
                    $('#bulkCertifyBtn').prop('disabled', !anyChecked);
                }

                // Handle certify button click
                $('#invoicesTable tbody').on('click', '.certify-btn', function() {
                    const invoiceId = $(this).data('invoice-id');
                    const invoiceNumber = $(this).data('invoice-number') || 'this invoice';

                    Swal.fire({
                        title: 'Confirm Certification',
                        html: `<div class="text-center">
                            <i class="fas fa-certificate fa-3x text-primary mb-3"></i>
                            <p>Are you sure you want to certify invoice <strong>${invoiceNumber}</strong> with FNE?</p>
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

                // Handle bulk certify button click
                $('#bulkCertifyBtn').click(function() {
                    const selectedIds = $('.invoice-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();

                    Swal.fire({
                        title: 'Confirm Bulk Certification',
                        html: `<div class="text-center">
                            <i class="fas fa-certificate fa-3x text-primary mb-3"></i>
                            <p>You are about to certify <strong>${selectedIds.length}</strong> invoices.</p>
                            <p class="text-muted">This process might take a few moments.</p>
                        </div>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, certify them!',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'btn btn-primary px-4',
                            cancelButton: 'btn btn-outline-secondary px-4'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            bulkCertifyInvoices(selectedIds);
                        }
                    });
                });

                // Handle retry button click
                $('.retry-btn').click(function() {
                    const invoiceId = $(this).data('invoice-id');
                    const invoiceNumber = $(this).data('invoice-number') || 'this invoice';

                    Swal.fire({
                        title: 'Confirm Retry',
                        html: `<div class="text-center">
                            <i class="fas fa-redo fa-3x text-warning mb-3"></i>
                            <p>Retry certification for invoice <strong>${invoiceNumber}</strong>?</p>
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
                            certifyInvoice(invoiceId);
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
                                html: `<div class="text-center"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><p>${xhr.responseJSON?.message || 'An error occurred'}</p></div>`,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-danger px-4'
                                },
                                buttonsStyling: false
                            });
                        }
                    });
                }

                function bulkCertifyInvoices(invoiceIds) {
                    $.ajax({
                        url: '/fne/invoices/bulk-certify',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            invoice_ids: invoiceIds
                        },
                        beforeSend: function() {
                            Swal.fire({
                                title: 'Processing',
                                html: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i><p>Certifying selected invoices...</p></div>',
                                allowOutsideClick: false,
                                showConfirmButton: false
                            });
                        },
                        success: function(response) {
                            let successCount = response.successful;
                            let totalCount = response.total;

                            if (successCount === totalCount) {
                                Swal.fire({
                                    title: 'Success!',
                                    html: `<div class="text-center"><i class="fas fa-check-circle fa-3x text-success mb-3"></i><p>All ${successCount} invoices certified successfully!</p></div>`,
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
                                    title: 'Partial Success',
                                    html: `<div class="text-center">
                                    <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                                    <p>Certified ${successCount} of ${totalCount} invoices.</p>
                                    <button class="btn btn-primary mt-3" id="viewDetailsBtn">
                                        <i class="fas fa-list me-1"></i> View Details
                                    </button>
                                </div>`,
                                    showConfirmButton: false,
                                    allowOutsideClick: false
                                });

                                $('#viewDetailsBtn').click(function() {
                                    let detailsHtml =
                                        '<div class="text-start"><ul class="list-unstyled">';
                                    response.results.forEach(result => {
                                        detailsHtml += `<li class="mb-2">
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">${result.success ? '✅' : '❌'}</span>
                                            <div>
                                                <strong>${result.invoice_number}</strong>
                                                <div class="text-muted small">${result.message}</div>
                                            </div>
                                        </div>
                                    </li>`;
                                    });
                                    detailsHtml += '</ul></div>';

                                    Swal.fire({
                                        title: 'Certification Results',
                                        html: detailsHtml,
                                        width: '800px',
                                        confirmButtonText: 'Close',
                                        customClass: {
                                            confirmButton: 'btn btn-secondary px-4'
                                        },
                                        buttonsStyling: false
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Error!',
                                html: `<div class="text-center"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><p>${xhr.responseJSON?.message || 'An error occurred during bulk certification'}</p></div>`,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-danger px-4'
                                },
                                buttonsStyling: false
                            });
                        }
                    });
                }

                // Handle sync form submission
                $('#syncForm').submit(function(e) {
                    e.preventDefault();

                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'POST',
                        data: $(this).serialize(),
                        beforeSend: function() {
                            $('#syncModal').modal('hide');
                            Swal.fire({
                                title: 'Syncing',
                                html: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i><p>Fetching and updating invoice statuses...</p></div>',
                                allowOutsideClick: false,
                                showConfirmButton: false
                            });
                        },
                        success: function(response) {
                            Swal.fire({
                                title: 'Sync Complete!',
                                html: `<div class="text-center"><i class="fas fa-check-circle fa-3x text-success mb-3"></i><p>Updated status for ${response.updated} invoices</p></div>`,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-success px-4'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                window.location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Error!',
                                html: `<div class="text-center"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><p>${xhr.responseJSON?.message || 'An error occurred during sync'}</p></div>`,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-danger px-4'
                                },
                                buttonsStyling: false
                            });
                        }
                    });
                });
            });
        </script>
    @endsection

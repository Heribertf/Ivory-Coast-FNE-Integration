import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


document.addEventListener('DOMContentLoaded', function () {
    // Bulk select functionality
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.invoice-checkbox');
    const bulkCertifyBtn = document.getElementById('bulkCertifyBtn');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            toggleBulkCertifyBtn();
        });
    }

    if (checkboxes) {
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', toggleBulkCertifyBtn);
        });
    }

    function toggleBulkCertifyBtn() {
        const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        bulkCertifyBtn.disabled = !anyChecked;
    }

    // Bulk certify handler
    if (bulkCertifyBtn) {
        bulkCertifyBtn.addEventListener('click', function () {
            const selectedIds = Array.from(checkboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value);

            if (selectedIds.length === 0) return;

            if (confirm(`Certify ${selectedIds.length} selected invoices?`)) {
                fetch('{{ route("fne.invoices.bulk-certify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ invoice_ids: selectedIds })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.successful > 0) {
                            alert(`${data.successful} invoices certified successfully!`);
                            window.location.reload();
                        } else {
                            alert('No invoices were certified. Check the results for details.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred during bulk certification.');
                    });
            }
        });
    }
});
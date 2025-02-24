$(document).ready(function() {
    // Initialize Monthly Trends Chart
    const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
    new Chart(monthlyTrendsCtx, {
        type: 'line',
        data: {
            labels: monthlyStats.map(stat => {
                const [year, month] = stat.month.split('-');
                return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            }),
            datasets: [
                {
                    label: 'Total Borrowings',
                    data: monthlyStats.map(stat => stat.total_borrowings),
                    borderColor: '#3498db',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Overdue Books',
                    data: monthlyStats.map(stat => stat.overdue_count),
                    borderColor: '#e74c3c',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Initialize Categories Chart
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: categoryStats.map(cat => cat.category),
            datasets: [{
                data: categoryStats.map(cat => cat.count),
                backgroundColor: [
                    '#3498db',
                    '#2ecc71',
                    '#e74c3c',
                    '#f1c40f',
                    '#9b59b6',
                    '#1abc9c',
                    '#e67e22'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
});

// Export table to CSV
function exportTableToCSV(tableId) {
    const table = document.getElementById(tableId);
    let csv = [];
    
    // Get headers
    const headers = [];
    const headerCells = table.querySelectorAll('thead th');
    headerCells.forEach(cell => {
        headers.push(cell.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Get data
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const data = [];
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            // Escape quotes and wrap in quotes to handle commas in content
            data.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(data.join(','));
    });
    
    // Download CSV file
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `library_report_${tableId}_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Add fade-in animation to cards
document.querySelectorAll('.card').forEach(card => {
    card.classList.add('fade-in');
});

// Initialize tooltips
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

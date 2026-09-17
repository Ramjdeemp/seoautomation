document.addEventListener('DOMContentLoaded', () => {
    const seoForm = document.getElementById('seoForm');
    const logoutBtn = document.getElementById('logoutBtn');

    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            console.log("Logout button clicked!");
            window.location.href = 'logout.php'; 
        });
    }

    // new date inputssss
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    if (startDateInput && endDateInput) {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        // Format to YYYY-MM-DD for the HTML input
        endDateInput.value = today.toISOString().split('T')[0];
        startDateInput.value = thirtyDaysAgo.toISOString().split('T')[0];
    }

    // this shit js alls the function to load websites & avatar ---
    loadProperties();

    if (seoForm) {
        seoForm.addEventListener('submit', async (e) => {
            e.preventDefault(); 
            const analyzeBtn = document.getElementById('analyzeBtn');
            const originalBtnText = analyzeBtn.textContent;
            analyzeBtn.textContent = "Analyzing...";
            analyzeBtn.disabled = true;
            try {
                const formData = new FormData(seoForm);
                const response = await fetch('seoautomator.php', {
                    method: 'POST',
                    body: formData
                });
                
                // FIXED: Redirect to your visual signin.html page
                if (response.status === 401) {
                    window.location.href = 'signin.html';
                    return;
                }
                const jsonResponse = await response.json();
                if (!response.ok) {
                    throw new Error(jsonResponse.error || "Server error occurred");
                }
                updateDashboard(jsonResponse.data);

            } catch (error) {
                console.error("Fetch error:", error);
                alert("Error fetching data: " + error.message);
            } finally {
                analyzeBtn.textContent = originalBtnText;
                analyzeBtn.disabled = false;
            }
        });
    }
});

// --- MISSING: The actual function to fetch domains from Google ---
async function loadProperties() {
    try {
        const response = await fetch('seoautomator.php');
        
        if (response.status === 401) {
            window.location.href = 'signin.html';
            return; 
        }
        
        const data = await response.json();
        console.log("API Response:", data); // <-- This will reveal exactly what Google is doing!
        
        // NEW: Catch 500 errors and update the UI so it doesn't fail silently
        if (!response.ok) {
            const select = document.getElementById('selectedProperty');
            select.innerHTML = `<option value="">Error: ${data.error || "Failed to load"}</option>`;
            select.disabled = true;
            return;
        }
        
        if (data.user) {
            const avatar = document.getElementById('userAvatar');
            if (data.user.profilepic && avatar) {
                avatar.src = data.user.profilepic;
                avatar.style.display = 'block';
                avatar.title = data.user.email; 
            }
        }

        if (data.properties) {
            const select = document.getElementById('selectedProperty');
            const analyzeBtn = document.getElementById('analyzeBtn');
            
            if (data.properties.length === 0) {
                select.innerHTML = '<option value="">No websites found</option>';
                select.disabled = true; 
                analyzeBtn.disabled = true; 
                analyzeBtn.style.opacity = '0.5'; 
                analyzeBtn.style.cursor = 'not-allowed';
                return; 
            }

            select.innerHTML = '<option value="">Select a website</option>'; 
            data.properties.forEach(siteUrl => {
                const option = document.createElement('option');
                option.value = siteUrl; 
                option.textContent = siteUrl;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error("Failed to load properties:", error);
    }
}

function updateDashboard(data) {
    const tbody = document.getElementById('keywordTableBody');
    tbody.innerHTML = ''; 
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="5">No data found for this date range.</td></tr>';
        document.getElementById('keywordCount').textContent = `0 keywords`;
        return;
    }
    let totalClicks = 0;
    let totalImpressions = 0;
    let totalCtr = 0;
    let totalPosition = 0;
    document.getElementById('keywordCount').textContent = `${data.length} keywords`;
    data.forEach(item => {
        totalClicks += item.clicks;
        totalImpressions += item.impressions;
        totalCtr += item.ctr;
        totalPosition += item.position;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.keyword}</td>
            <td>${item.clicks}</td>
            <td>${item.impressions}</td>
            <td>${(item.ctr * 100).toFixed(2)}%</td>
            <td>${item.position.toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });
    document.getElementById('totalClicks').textContent = totalClicks.toLocaleString();
    document.getElementById('totalImpressions').textContent = totalImpressions.toLocaleString();
    document.getElementById('averageCtr').textContent = ((totalCtr / data.length) * 100).toFixed(2) + '%';
    document.getElementById('averagePosition').textContent = (totalPosition / data.length).toFixed(2);
}
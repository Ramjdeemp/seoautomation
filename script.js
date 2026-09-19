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
            const email = data.user.email;
            
            if (data.user.profilepic) {
                const img = new Image();
                img.onload = () => {
                    avatar.src = data.user.profilepic;
                    avatar.style.display = 'block';
                    avatar.title = email;
                };
                img.onerror = () => {
                    showInitialAvatar(avatar, email);
                };
                img.src = data.user.profilepic;
            } else {
                showInitialAvatar(avatar, email);
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
            <td>
                <a href="${item.page}" target="_blank" style="color: #8b7355; text-decoration: underline; font-size: 13px;">
                    View Page &#8599;
                </a>
            </td>
            <td>${item.clicks}</td>
            <td>${item.impressions}</td>
            <td>${(item.ctr * 100).toFixed(2)}%</td>
            <td>${item.position.toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });
document.getElementById('totalClicks').textContent = totalClicks.toLocaleString();
document.getElementById('totalImpressions').textContent = totalImpressions.toLocaleString();

// CTR: (total clicks / total impressions) * 100
const actualAverageCtr = totalImpressions > 0 
    ? ((totalClicks / totalImpressions) * 100).toFixed(2)
    : '0.00';
document.getElementById('averageCtr').textContent = actualAverageCtr + '%';

// Position: average of all positions
const actualAveragePosition = data.length > 0 
    ? (totalPosition / data.length).toFixed(2)
    : '0.00';
document.getElementById('averagePosition').textContent = actualAveragePosition;
}
function showInitialAvatar(avatarElement, email) {
    const initials = email.split('@')[0].substring(0, 2).toUpperCase();
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F'];
    const hash = email.charCodeAt(0) + email.charCodeAt(email.length - 1);
    const bgColor = colors[hash % colors.length];
    
    const svg = `
        <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <circle cx="20" cy="20" r="20" fill="${bgColor}"/>
            <circle cx="20" cy="14" r="5" fill="white"/>
            <ellipse cx="20" cy="28" rx="8" ry="6" fill="white"/>
        </svg>
    `;
    
    avatarElement.src = 'data:image/svg+xml;base64,' + btoa(svg);
    avatarElement.style.display = 'block';
    avatarElement.title = email;
}
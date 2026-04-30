async function callAPI(data) {
    const fd = new FormData();
    for (const key in data) fd.append(key, data[key]);
    const res = await fetch('api.php', { method: 'POST', body: fd });
    return await res.json();
}

function showAlert(message, type) {
    const alertBox = document.getElementById('alertBox');
    if (!alertBox) return;
    
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
    alertBox.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
    alertBox.className = `alert show alert-${type}`;
    
    setTimeout(() => {
        alertBox.classList.remove('show');
    }, 4000);
}

// ===== LOGIN LOGIC =====
if (document.getElementById('loginForm')) {
    document.getElementById('loginForm').onsubmit = async (e) => {
        e.preventDefault();

        const username = document.getElementById('u').value.trim();
        const password = document.getElementById('p').value;

        showAlert('Signing in...', 'warning');

        const res = await callAPI({
            action: 'login',
            username: username,
            password: password
        });

        if (res.status === 'success') {
            showAlert('✓ Login successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1000);
        } else {
            showAlert('✗ Invalid username or password', 'error');
        }
    };
}

// ===== REGISTRATION LOGIC =====
if (document.getElementById('regForm')) {
    document.getElementById('regForm').onsubmit = async (e) => {
        e.preventDefault();

        const password = document.getElementById('p').value;
        
        if (password.length < 6) {
            showAlert('✗ Password must be at least 6 characters', 'error');
            return;
        }

        showAlert('Creating account...', 'warning');

        const res = await callAPI({
            action: 'register',
            username: document.getElementById('u').value,
            password: password,
            fName: document.getElementById('fn').value,
            lName: document.getElementById('ln').value,
            email: document.getElementById('em').value,
            contactnumber: document.getElementById('cn').value,
            sex: document.getElementById('sx').value,
            address: document.getElementById('ad').value
        });

        if (res.status === 'success') {
            showAlert('✓ Account created successfully! Redirecting to login...', 'success');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1500);
        } else {
            showAlert('✗ ' + (res.message || 'Registration failed. Username may already exist.'), 'error');
        }
    };
}

// ===== DASHBOARD LOGIC =====
if (document.getElementById('mainContent')) {
    window.onload = async () => {
        const res = await callAPI({ action: 'check_session' });

        if (res.status !== 'success') {
            window.location.href = 'login.html';
            return;
        }

        const user = res.data;
        const fullName = `${user.fName || ''} ${user.lName || ''}`.trim() || user.username;
        
        // Update UI
        document.getElementById('nameDisplay').textContent = `Welcome back, ${fullName}! 👋`;
        document.getElementById('nameDisplay2').textContent = fullName;
        document.getElementById('avatar').textContent = fullName.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        document.getElementById('roleBadge').textContent = (user.username === 'admin') ? 'Administrator' : 'Employee';

        const menu = document.getElementById('menuLinks');
        const isAdmin = user.username === 'admin';

        if (isAdmin) {
            document.getElementById('adminView').style.display = 'block';
            menu.innerHTML = `
                <li><a href="#" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-users"></i> Employees</a></li>
                <li><a href="#"><i class="fas fa-file-invoice-dollar"></i> Payroll</a></li>
                <li><a href="#"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="leaverequest.html"><i class="fas fa-umbrella-beach"></i> <span>Leave Request</span></a></li>

                <li><a href="#"><i class="fas fa-file-alt"></i> Reports</a></li>
            `;
        } else {
            document.getElementById('userView').style.display = 'block';
            menu.innerHTML = `
                <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-file-pdf"></i> Payslips</a></li>
                <li><a href="leaverequest.html"><i class="fas fa-umbrella-beach"></i> <span>Leave Request</span></a></li>
                <li><a href="#"><i class="fas fa-clock"></i> Attendance</a></li>
                <li><a href="myprofile.html"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
                
            `;
        }
    };
}

// ===== LOGOUT LOGIC =====
if (document.getElementById('logoutBtn')) {
    document.getElementById('logoutBtn').onclick = async () => {
        if (confirm('Are you sure you want to logout?')) {
            await callAPI({ action: 'logout' });
            window.location.href = 'login.html';
        }
    };
}
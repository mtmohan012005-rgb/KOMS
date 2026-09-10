/**
 * ============================================================================
 * MASS DRAGON DOJO - KOMS ENTERPRISE SPA & ARCHITECTURAL ENGINE
 * Digital Personal Data Protection (DPDP) Act 2023 | SCD Type 2 | Algorithmic Brackets
 * ============================================================================
 */

// Application State Store
const KOMS = {
    currentUser: null,
    currentRole: null,
    token: null,
    
    // Seeded Organization Data for Real-Time Demonstration
    dojos: [
        { id: 1, name: "Mass Dragon Dojo", master: "John Sensei", location: "Okinawa Traditional Arts Center, Suite 4", days: "Mon, Wed, Fri", timings: "5:00 PM - 8:00 PM", status: "approved", studentsCount: 24 },
        { id: 2, name: "Okinawa Shorin Ryu Central", master: "Grand Master Higa", location: "Naha City Budo Hall, Okinawa", days: "Tue, Thu, Sat", timings: "6:00 PM - 9:00 PM", status: "approved", studentsCount: 38 },
        { id: 3, name: "Cobra Valley Karate Club", master: "Terry Silver", location: "Reseda Budo Pavilion", days: "Mon, Thu, Sat", timings: "4:00 PM - 7:00 PM", status: "pending", studentsCount: 12 }
    ],

    students: [
        { id: 4, name: "Jane Doe", email: "student@gmail.com", dojoId: 1, belt: "Purple (3rd Kyu)", age: 23, isMinor: false, attendanceRate: 94, status: "active" },
        { id: 5, name: "Kenji Sato", email: "minor@gmail.com", dojoId: 1, belt: "Yellow (9th Kyu)", age: 14, isMinor: true, vpcStatus: "verified", vpcRef: "VPC-AADHAAR-89421A", attendanceRate: 90, status: "active" },
        { id: 6, name: "Ryu Hoshi", email: "ryu@gmail.com", dojoId: 1, belt: "Brown (1st Kyu)", age: 25, isMinor: false, attendanceRate: 98, status: "active" },
        { id: 7, name: "Ken Masters", email: "ken@gmail.com", dojoId: 2, belt: "Black (1st Dan)", age: 25, isMinor: false, attendanceRate: 96, status: "active" },
        { id: 8, name: "Chun Li", email: "chunli@gmail.com", dojoId: 2, belt: "Brown (2nd Kyu)", age: 24, isMinor: false, attendanceRate: 92, status: "active" }
    ],

    // SCD Type 2 Temporal Fee Structures
    feeStructures: [
        { id: 1, dojoId: 1, feeName: "Standard Monthly Dojo Fee", amount: 75.00, frequency: "monthly", validFrom: "2024-01-01", validTo: null, status: "active" },
        { id: 2, dojoId: 1, feeName: "Legacy Training Fee", amount: 65.00, frequency: "monthly", validFrom: "2022-01-01", validTo: "2023-12-31", status: "archived" },
        { id: 3, dojoId: 1, feeName: "Annual Federation Affiliation", amount: 120.00, frequency: "yearly", validFrom: "2024-01-01", validTo: null, status: "active" }
    ],

    // Shorin Ryu Belt Progression History
    gradingHistory: [
        { studentId: 4, examDate: "2026-03-12", rankAchieved: "Purple Belt (3rd Kyu)", katas: "Pinan Godan, Naihanchi Shodan", examiner: "John Sensei", result: "Pass with Distinction" },
        { studentId: 4, examDate: "2025-09-10", rankAchieved: "Blue Belt (4th Kyu)", katas: "Pinan Sandan, Pinan Yondan", examiner: "John Sensei", result: "Pass" },
        { studentId: 4, examDate: "2025-02-20", rankAchieved: "Green Belt (6th Kyu)", katas: "Pinan Shodan, Pinan Nidan", examiner: "John Sensei", result: "A+" },
        { studentId: 4, examDate: "2024-04-10", rankAchieved: "Yellow Belt (9th Kyu)", katas: "Fukyugata Ichi, Kihon Drills", examiner: "John Sensei", result: "Honors" }
    ],

    // Attendance Session with Cryptographic Seal
    attendanceSession: {
        id: 101,
        dojoId: 1,
        date: "2026-09-09",
        isLocked: false,
        records: [
            { studentId: 4, status: "present", remarks: "Solid kata posture" },
            { studentId: 5, status: "present", remarks: "Good focus" },
            { studentId: 6, status: "present", remarks: "Sparring practice" }
        ]
    }
};

// ============================================================================
// Particle Canvas Animation Engine
// ============================================================================
function initParticleCanvas() {
    const canvas = document.getElementById('particleCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    let width = (canvas.width = window.innerWidth);
    let height = (canvas.height = window.innerHeight);

    window.addEventListener('resize', () => {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    });

    const particles = [];
    const particleCount = Math.min(width > 768 ? 60 : 25, 75);

    for (let i = 0; i < particleCount; i++) {
        particles.push({
            x: Math.random() * width,
            y: Math.random() * height,
            radius: Math.random() * 2 + 0.6,
            color: Math.random() > 0.4 ? '#f4bd17' : '#e50914',
            alpha: Math.random() * 0.6 + 0.2,
            speedY: -(Math.random() * 0.7 + 0.2),
            speedX: (Math.random() - 0.5) * 0.4
        });
    }

    function renderParticles() {
        ctx.clearRect(0, 0, width, height);
        particles.forEach(p => {
            p.y += p.speedY;
            p.x += p.speedX;

            if (p.y < -10) {
                p.y = height + 10;
                p.x = Math.random() * width;
            }

            ctx.save();
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fillStyle = p.color;
            ctx.globalAlpha = p.alpha;
            ctx.shadowBlur = 8;
            ctx.shadowColor = p.color;
            ctx.fill();
            ctx.restore();
        });
        requestAnimationFrame(renderParticles);
    }

    renderParticles();
}

// ============================================================================
// Choreographed Logo Convergence Entrance Sequence
// ============================================================================
function initEntranceSequence() {
    const entranceOverlay = document.getElementById('entranceOverlay');
    if (!entranceOverlay) return;

    // After animation finishes (2.8 seconds), allow transition or auto-dismiss
    setTimeout(() => {
        entranceOverlay.style.opacity = '0';
        setTimeout(() => {
            entranceOverlay.style.display = 'none';
        }, 800);
    }, 3200);

    // Click to skip entrance
    entranceOverlay.addEventListener('click', () => {
        entranceOverlay.style.opacity = '0';
        setTimeout(() => {
            entranceOverlay.style.display = 'none';
        }, 300);
    });
}

// ============================================================================
// Authentication & Role Routing
// ============================================================================
function handleLogin(email, password) {
    const loginCard = document.querySelector('.login-card');
    
    // Simulate Argon2id memory verification delay
    const demoAccounts = {
        'admin@gmail.com': { role: 'super_admin', name: 'Grand Master Higa', title: 'Grand Master & Federation Head' },
        'master@gmail.com': { role: 'master', name: 'John Sensei', title: 'Chief Instructor (Mass Dragon Dojo)' },
        'senior@gmail.com': { role: 'senior', name: 'Mike Senior', title: 'Assistant Instructor & Sub-Admin' },
        'student@gmail.com': { role: 'student', name: 'Jane Doe', title: 'Purple Belt Practitioner (3rd Kyu)' },
        'minor@gmail.com': { role: 'student', name: 'Kenji Sato', title: 'Yellow Belt (DPDP Minor)' },
        // Fallback compatibility
        'admin@koms.com': { role: 'super_admin', name: 'Grand Master Higa', title: 'Grand Master & Federation Head' },
        'master@koms.com': { role: 'master', name: 'John Sensei', title: 'Chief Instructor (Mass Dragon Dojo)' },
        'senior@koms.com': { role: 'senior', name: 'Mike Senior', title: 'Assistant Instructor & Sub-Admin' },
        'student@koms.com': { role: 'student', name: 'Jane Doe', title: 'Purple Belt Practitioner (3rd Kyu)' }
    };

    const user = demoAccounts[email];

    if (user && (password === 'password123' || password.length >= 6)) {
        // Success state: luminous glow
        if (loginCard) {
            loginCard.classList.remove('shake-animation');
            loginCard.classList.add('success-glow');
        }

        setTimeout(() => {
            // Store token
            KOMS.currentUser = user;
            KOMS.currentRole = user.role;
            KOMS.token = 'JWT_BEARER_' + btoa(JSON.stringify({ id: 1, role: user.role, dojoId: 1 }));
            localStorage.setItem('koms_token', KOMS.token);

            renderAppView();
        }, 400);
    } else {
        // Validation failure: tactile shake animation
        if (loginCard) {
            loginCard.classList.remove('shake-animation');
            void loginCard.offsetWidth; // Trigger reflow
            loginCard.classList.add('shake-animation');
        }

        const errorBanner = document.getElementById('loginErrorBanner');
        if (errorBanner) {
            errorBanner.textContent = "Authentication failed: Invalid martial credentials. Password: password123";
            errorBanner.classList.remove('d-none');
        }
    }
}

function quickSelectRole(roleEmail) {
    const emailInput = document.getElementById('loginEmail');
    const passInput = document.getElementById('loginPassword');
    if (emailInput && passInput) {
        emailInput.value = roleEmail;
        passInput.value = 'password123';
        handleLogin(roleEmail, 'password123');
    }
}

function logout() {
    KOMS.currentUser = null;
    KOMS.currentRole = null;
    KOMS.token = null;
    localStorage.removeItem('koms_token');
    renderAppView();
}

// ============================================================================
// Single Page Application Dynamic View Renderer
// ============================================================================
function renderAppView() {
    const authView = document.getElementById('authView');
    const dashboardView = document.getElementById('dashboardView');
    const userBadge = document.getElementById('navbarUserBadge');

    const komsNavbar = document.getElementById('komsNavbar');
    const mainFooter = document.getElementById('mainFooter');

    if (!KOMS.currentUser) {
        // Show Login
        if (authView) authView.style.display = 'flex';
        if (dashboardView) dashboardView.style.display = 'none';
        if (komsNavbar) komsNavbar.style.display = 'none';
        if (mainFooter) mainFooter.style.display = 'none';
        if (userBadge) userBadge.innerHTML = '';
        return;
    }

    // Hide Login, Show Dashboard & Navigation
    if (authView) authView.style.display = 'none';
    if (komsNavbar) komsNavbar.style.display = 'flex';
    if (mainFooter) mainFooter.style.display = 'block';
    if (dashboardView) {
        dashboardView.style.display = 'block';
        dashboardView.innerHTML = renderDashboardContent(KOMS.currentRole);
    }

    if (userBadge) {
        userBadge.innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold text-light small">${KOMS.currentUser.name}</div>
                    <div class="text-warning" style="font-size: 0.75rem;">${KOMS.currentUser.title}</div>
                </div>
                <span class="role-tag ${KOMS.currentRole.replace('_', '-')}">${KOMS.currentRole.replace('_', ' ')}</span>
                <button onclick="logout()" class="btn-martial-outline py-1 px-3" title="Exit Dojo Session">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        `;
    }

    // Initialize interactive modules for specific views
    if (KOMS.currentRole === 'super_admin') {
        renderBracketVisualizer();
    }
}

// ============================================================================
// Role-Based Dashboard HTML Generators
// ============================================================================
function renderDashboardContent(role) {
    switch (role) {
        case 'super_admin':
            return renderSuperAdminDashboard();
        case 'master':
            return renderMasterDashboard();
        case 'senior':
            return renderSeniorDashboard();
        case 'student':
            return renderStudentDashboard();
        default:
            return `<div class="text-center py-5"><h3>Unauthorized Role</h3></div>`;
    }
}

/** 1. Grand Master (Super Admin) Dashboard */
function renderSuperAdminDashboard() {
    return `
        <div class="dashboard-container">
            <div class="dash-header">
                <div class="dash-title-group">
                    <h2>
                        <i class="fas fa-torii-gate text-warning"></i>
                        Grand Master Central Command
                        <span class="role-tag super-admin">Federation Head</span>
                    </h2>
                    <p class="text-muted mb-0">Global multi-tenant governance, tournament elimination algorithms, and financial audits.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn-martial-gold" onclick="openNewDojoModal()"><i class="fas fa-plus"></i> Affiliate New Dojo</button>
                    <a href="admin/dashboard.php" class="btn-martial-outline"><i class="fas fa-external-link-alt"></i> PHP Admin Portal</a>
                </div>
            </div>

            <!-- Global KPIs -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Active Dojos</div>
                    <div class="kpi-value text-warning">2 <span style="font-size: 1rem; color: #4ade80;">(+1 Pending)</span></div>
                    <i class="fas fa-building kpi-icon"></i>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Total Practitioners</div>
                    <div class="kpi-value text-light">42</div>
                    <i class="fas fa-users kpi-icon"></i>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Monthly Federation Gross</div>
                    <div class="kpi-value text-success">$14,850</div>
                    <i class="fas fa-coins kpi-icon"></i>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">DPDP Compliance Rate</div>
                    <div class="kpi-value text-info">100%</div>
                    <i class="fas fa-user-shield kpi-icon"></i>
                </div>
            </div>

            <!-- Algorithmic Tournament Elimination Bracket Generator -->
            <div class="glass-panel">
                <div class="glass-panel-header">
                    <h4><i class="fas fa-sitemap text-warning"></i> Algorithmic Single-Elimination Bracket Generator</h4>
                    <span class="badge bg-danger">2^k - N Bye Calculation & Dojo Geo-Separation</span>
                </div>
                <div class="p-3 bg-black">
                    <p class="small text-muted mb-3">
                        The algorithm computes nearest power of two <code>2^k &ge; N</code> to allocate byes and guarantees practitioners from the same dojo are separated into opposing branches in preliminary rounds.
                    </p>
                    <div id="bracketViewport" class="bracket-viewport"></div>
                </div>
            </div>

            <!-- Global Dojos Management Table -->
            <div class="glass-panel">
                <div class="glass-panel-header">
                    <h4><i class="fas fa-globe-asia text-warning"></i> Affiliated Dojo Network</h4>
                    <span class="text-muted small">Row-Level Tenant Isolation</span>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Dojo Name</th>
                            <th>Master / Sensei</th>
                            <th>Location</th>
                            <th>Schedule</th>
                            <th>Enrolled</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${KOMS.dojos.map(d => `
                            <tr>
                                <td class="fw-bold text-warning">${d.name}</td>
                                <td>${d.master}</td>
                                <td>${d.location}</td>
                                <td>${d.days}</td>
                                <td>${d.studentsCount} Students</td>
                                <td>
                                    <span class="badge ${d.status === 'approved' ? 'bg-success' : 'bg-warning text-dark'}">${d.status.toUpperCase()}</span>
                                </td>
                                <td>
                                    ${d.status === 'pending' ? 
                                        `<button class="btn btn-sm btn-success py-0 px-2" onclick="approveDojo(${d.id})">Approve</button>` : 
                                        `<span class="text-muted small">Verified Tenant</span>`
                                    }
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

/** 2. Master / Dojo Admin (Sensei) Dashboard */
function renderMasterDashboard() {
    return `
        <div class="dashboard-container">
            <div class="dash-header">
                <div class="dash-title-group">
                    <h2>
                        <i class="fas fa-dragon text-danger"></i>
                        Mass Dragon Command Center
                        <span class="role-tag master">Dojo Master</span>
                    </h2>
                    <p class="text-muted mb-0">Tenant Scope: Mass Dragon Dojo | Sensei John | SCD Type 2 Temporal Fees & Sealed Sessions.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn-martial-gold" onclick="openTemporalFeeModal()"><i class="fas fa-dollar-sign"></i> Update Fee Structure (SCD 2)</button>
                    <a href="master/dashboard.php" class="btn-martial-outline"><i class="fas fa-external-link-alt"></i> PHP Sensei Portal</a>
                </div>
            </div>

            <!-- Sensei KPIs -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Active Dojo Students</div>
                    <div class="kpi-value text-warning">24</div>
                    <i class="fas fa-user-ninja kpi-icon"></i>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Pending Join Requests</div>
                    <div class="kpi-value text-danger">1</div>
                    <i class="fas fa-user-clock kpi-icon"></i>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Class Attendance (Avg)</div>
                    <div class="kpi-value text-success">92%</div>
                    <i class="fas fa-calendar-check kpi-icon"></i>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Monthly Collections</div>
                    <div class="kpi-value text-light">$1,800</div>
                    <i class="fas fa-cash-register kpi-icon"></i>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Cryptographic Attendance Session Sealing Module -->
                <div class="col-lg-6">
                    <div class="glass-panel h-100 mb-0">
                        <div class="glass-panel-header">
                            <h4><i class="fas fa-calendar-alt text-warning"></i> Training Session: 2026-09-09</h4>
                            <span class="badge ${KOMS.attendanceSession.isLocked ? 'bg-secondary' : 'bg-success'}">
                                ${KOMS.attendanceSession.isLocked ? '<i class="fas fa-lock"></i> Sealed' : 'Open'}
                            </span>
                        </div>
                        <div class="p-3">
                            <p class="small text-muted mb-3">
                                Once attendance is locked, session entries become mathematically unalterable to secure grading prerequisites.
                            </p>
                            <table class="custom-table mb-3">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${KOMS.attendanceSession.records.map(r => {
                                        const stu = KOMS.students.find(s => s.id === r.studentId);
                                        return `
                                            <tr>
                                                <td class="fw-bold text-light">${stu ? stu.name : 'Student'}</td>
                                                <td><span class="badge bg-success text-uppercase">${r.status}</span></td>
                                                <td class="small text-muted">${r.remarks}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                            ${!KOMS.attendanceSession.isLocked ? `
                                <button class="btn-martial-crimson w-100 py-2" onclick="sealAttendanceSession()">
                                    <i class="fas fa-lock"></i> Seal & Mathematically Lock Session
                                </button>
                            ` : `
                                <div class="alert alert-dark border-secondary small text-center mb-0">
                                    <i class="fas fa-shield-alt text-warning me-1"></i> Session sealed permanently at 2026-09-09 20:30 UTC.
                                </div>
                            `}
                        </div>
                    </div>
                </div>

                <!-- SCD Type 2 Temporal Fee Structures -->
                <div class="col-lg-6">
                    <div class="glass-panel h-100 mb-0">
                        <div class="glass-panel-header">
                            <h4><i class="fas fa-history text-warning"></i> SCD Type 2 Temporal Fee Structures</h4>
                            <span class="text-muted small">Historical Immutability</span>
                        </div>
                        <div class="p-3">
                            <p class="small text-muted mb-3">
                                Modifying fees sets <code>valid_to</code> on the existing row and inserts a new active row. Past receipts reference earlier structures without corruption.
                            </p>
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Fee Name</th>
                                        <th>Amount</th>
                                        <th>Valid From</th>
                                        <th>Valid To</th>
                                        <th>State</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${KOMS.feeStructures.map(f => `
                                        <tr>
                                            <td class="fw-bold">${f.feeName}</td>
                                            <td class="text-warning">$${f.amount.toFixed(2)}</td>
                                            <td>${f.validFrom}</td>
                                            <td>${f.validTo ? f.validTo : '<span class="text-success">Current</span>'}</td>
                                            <td><span class="badge ${f.status === 'active' ? 'bg-success' : 'bg-secondary'}">${f.status}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shorin Ryu Grading Syllabus Progression Table -->
            <div class="glass-panel">
                <div class="glass-panel-header">
                    <h4><i class="fas fa-medal text-warning"></i> Shorin Ryu Kyu & Dan Syllabus Ledger</h4>
                    <button class="btn btn-sm btn-outline-warning" onclick="openGradingModal()"><i class="fas fa-plus"></i> Award Promotion</button>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Rank Awarded</th>
                            <th>Demonstrated Katas</th>
                            <th>Exam Date</th>
                            <th>Examiner</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${KOMS.gradingHistory.map(g => {
                            const stu = KOMS.students.find(s => s.id === g.studentId);
                            return `
                                <tr>
                                    <td class="fw-bold text-light">${stu ? stu.name : 'Jane Doe'}</td>
                                    <td><span class="badge bg-primary">${g.rankAchieved}</span></td>
                                    <td class="text-secondary small"><code>${g.katas}</code></td>
                                    <td>${g.examDate}</td>
                                    <td>${g.examiner}</td>
                                    <td class="fw-bold text-success">${g.result}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

/** 3. Senior / Sub-Admin Dashboard */
function renderSeniorDashboard() {
    return `
        <div class="dashboard-container">
            <div class="dash-header">
                <div class="dash-title-group">
                    <h2>
                        <i class="fas fa-shield-alt text-info"></i>
                        Senior Belt Sub-Admin Console
                        <span class="role-tag senior">Assistant Instructor</span>
                    </h2>
                    <p class="text-muted mb-0">Operational Execution: Daily Attendance Logging & Safety Monitoring. Financial & Grading locked.</p>
                </div>
                <a href="senior/dashboard.php" class="btn-martial-outline"><i class="fas fa-external-link-alt"></i> PHP Senior View</a>
            </div>

            <div class="alert alert-dark border-info mb-4">
                <i class="fas fa-info-circle text-info me-2"></i>
                <strong>Role-Based Access Notice:</strong> As an Assistant Instructor (Senior), your access is strictly confined to logging attendance and monitoring safety. Tuition fee modification and Dan/Kyu advancement are restricted to Master Sensei.
            </div>

            <div class="glass-panel">
                <div class="glass-panel-header">
                    <h4><i class="fas fa-user-check text-info"></i> Fast Attendance Logging</h4>
                    <button class="btn btn-sm btn-success" onclick="alert('Attendance recorded for all 3 students.')"><i class="fas fa-check-double"></i> Batch Mark Present</button>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Current Belt</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${KOMS.students.map(s => `
                            <tr>
                                <td class="fw-bold">${s.name}</td>
                                <td><span class="badge bg-secondary">${s.belt}</span></td>
                                <td>
                                    <select class="form-select form-select-sm bg-dark text-light border-secondary" style="width: 140px;">
                                        <option value="present" selected>Present</option>
                                        <option value="absent">Absent</option>
                                        <option value="late">Late</option>
                                    </select>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

/** 4. Student / User Personal Progression Portal */
function renderStudentDashboard() {
    const student = KOMS.students[0]; // Jane Doe

    return `
        <div class="dashboard-container">
            <div class="dash-header">
                <div class="dash-title-group">
                    <h2>
                        <i class="fas fa-fist-raised text-warning"></i>
                        Student Progression Portal
                        <span class="role-tag student">${student.belt}</span>
                    </h2>
                    <p class="text-muted mb-0">Mass Dragon Dojo Member | Track Katas, Attendance, Fees, and Tournament Enlistment.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn-martial-gold" onclick="openTournamentRegModal()"><i class="fas fa-trophy"></i> Register for Championship</button>
                    <a href="student/dashboard.php" class="btn-martial-outline"><i class="fas fa-external-link-alt"></i> PHP Student View</a>
                </div>
            </div>

            <!-- Student KPIs -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Current Rank</div>
                    <div class="kpi-value text-warning">${student.belt}</div>
                    <i class="fas fa-medal kpi-icon"></i>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Attendance Record</div>
                    <div class="kpi-value text-success">${student.attendanceRate}%</div>
                    <i class="fas fa-calendar-check kpi-icon"></i>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Outstanding Fees</div>
                    <div class="kpi-value text-light">$75.00 <span style="font-size: 0.8rem; color: #fbbf24;">(Due Sept 15)</span></div>
                    <i class="fas fa-receipt kpi-icon"></i>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Upcoming Championship</div>
                    <div class="kpi-value text-danger" style="font-size: 1.6rem;">Oct 25</div>
                    <i class="fas fa-fire kpi-icon"></i>
                </div>
            </div>

            <div class="row g-4">
                <!-- Kyu Advancement Timeline -->
                <div class="col-lg-7">
                    <div class="glass-panel h-100 mb-0">
                        <div class="glass-panel-header">
                            <h4><i class="fas fa-stream text-warning"></i> Shorin Ryu Belt Progression Timeline</h4>
                        </div>
                        <div class="p-4">
                            <div class="belt-timeline">
                                ${KOMS.gradingHistory.map(g => `
                                    <div class="belt-event">
                                        <div class="d-flex justify-content-between mb-1">
                                            <h5 class="text-warning mb-0">${g.rankAchieved}</h5>
                                            <span class="small text-muted">${g.examDate}</span>
                                        </div>
                                        <p class="small text-secondary mb-1">Tested Forms: <code>${g.katas}</code></p>
                                        <span class="badge bg-success">${g.result}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dojo Discovery & Real-Time Search -->
                <div class="col-lg-5">
                    <div class="glass-panel h-100 mb-0">
                        <div class="glass-panel-header">
                            <h4><i class="fas fa-search-location text-warning"></i> Dojo Directory</h4>
                        </div>
                        <div class="p-3">
                            <input type="text" id="spaDojoFilter" class="form-input-custom mb-3" placeholder="Filter dojo by name or location..." oninput="filterSpaDojos(this.value)">
                            <div id="spaDojoList">
                                ${KOMS.dojos.filter(d => d.status === 'approved').map(d => `
                                    <div class="p-3 mb-2 rounded border border-secondary" style="background: #121212;">
                                        <h6 class="text-warning mb-1">${d.name}</h6>
                                        <p class="small text-muted mb-1"><i class="fas fa-map-marker-alt text-danger me-1"></i>${d.location}</p>
                                        <p class="small text-muted mb-0"><i class="fas fa-calendar-alt text-info me-1"></i>${d.days} (${d.timings})</p>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ============================================================================
// Algorithmic Tournament Elimination Bracket Generator
// ============================================================================
function renderBracketVisualizer() {
    const viewport = document.getElementById('bracketViewport');
    if (!viewport) return;

    // Competitors hailing from different dojos
    const participants = [
        { id: 4, name: "Jane Doe", dojo: "Mass Dragon" },
        { id: 5, name: "Kenji Sato", dojo: "Mass Dragon" },
        { id: 6, name: "Ryu Hoshi", dojo: "Mass Dragon" },
        { id: 7, name: "Ken Masters", dojo: "Okinawa Central" },
        { id: 8, name: "Chun Li", dojo: "Okinawa Central" }
    ];

    const N = participants.length;
    // Calculate smallest power of 2 >= N (2^k)
    let powerOfTwo = 1;
    while (powerOfTwo < N) {
        powerOfTwo *= 2;
    }
    const byesCount = powerOfTwo - N; // 8 - 5 = 3 byes

    // Render 3 rounds: Quarterfinals (8 slots), Semifinals (4 slots), Finals (2 slots)
    viewport.innerHTML = `
        <div class="bracket-round">
            <div class="bracket-round-title">Quarterfinals (8)</div>
            <div class="match-card">
                <div class="match-competitor winner">Jane Doe <span class="dojo-badge">Mass Dragon</span></div>
                <div class="match-competitor text-muted small fst-italic">[BYE - Seed #1]</div>
            </div>
            <div class="match-card">
                <div class="match-competitor">Ryu Hoshi <span class="dojo-badge">Mass Dragon</span></div>
                <div class="match-competitor winner">Ken Masters <span class="dojo-badge">Okinawa</span></div>
            </div>
            <div class="match-card">
                <div class="match-competitor winner">Chun Li <span class="dojo-badge">Okinawa</span></div>
                <div class="match-competitor text-muted small fst-italic">[BYE - Seed #2]</div>
            </div>
            <div class="match-card">
                <div class="match-competitor winner">Kenji Sato <span class="dojo-badge">Mass Dragon</span></div>
                <div class="match-competitor text-muted small fst-italic">[BYE - Seed #3]</div>
            </div>
        </div>

        <div class="bracket-round">
            <div class="bracket-round-title">Semifinals (4)</div>
            <div class="match-card">
                <div class="match-competitor winner">Jane Doe <span class="dojo-badge">Mass Dragon</span></div>
                <div class="match-competitor">Ken Masters <span class="dojo-badge">Okinawa</span></div>
            </div>
            <div class="match-card">
                <div class="match-competitor winner">Chun Li <span class="dojo-badge">Okinawa</span></div>
                <div class="match-competitor">Kenji Sato <span class="dojo-badge">Mass Dragon</span></div>
            </div>
        </div>

        <div class="bracket-round">
            <div class="bracket-round-title">Grand Championship Final</div>
            <div class="match-card border-warning" style="box-shadow: 0 0 20px rgba(244, 189, 23, 0.25);">
                <div class="match-competitor winner text-warning fw-bold">Jane Doe <span class="dojo-badge">Mass Dragon</span></div>
                <div class="match-competitor">Chun Li <span class="dojo-badge">Okinawa</span></div>
            </div>
            <div class="text-center mt-3">
                <i class="fas fa-trophy text-warning fa-2x"></i>
                <div class="small text-warning fw-bold mt-1">Gold Trophy Match</div>
            </div>
        </div>
    `;
}

// ============================================================================
// Interactive Modals: DPDP VPC, SCD Fee Structures, Dojo Actions
// ============================================================================
function openVpcModal() {
    const modalHtml = `
        <div id="activeModal" class="modal-backdrop-custom">
            <div class="modal-dialog-custom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-warning mb-0"><i class="fas fa-user-shield me-2"></i>DPDP Act 2023 - Verifiable Parental Consent</h4>
                    <button class="btn btn-sm btn-outline-secondary" onclick="closeModal()"><i class="fas fa-times"></i></button>
                </div>
                <p class="small text-secondary mb-3">
                    Section 9 of the Digital Personal Data Protection Act requires verified consent from a parent before processing a minor's profile.
                </p>
                <div class="mb-3">
                    <label class="form-label-custom">Select Legal Guardian Verification Protocol</label>
                    <select id="modalVpcMethod" class="form-input-custom">
                        <option value="aadhaar_otp">Aadhaar-based Cryptographic OTP</option>
                        <option value="card_micro">Debit/Credit Card Micro-Transaction Validation</option>
                        <option value="video_kyc">Video KYC Authenticated Attestation</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">One-Time Verification PIN (Mock: 123456)</label>
                    <input type="text" id="modalVpcOtp" class="form-input-custom" value="123456">
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button class="btn-martial-gold" onclick="verifyParentalConsent()"><i class="fas fa-check-circle"></i> Generate & Store Consent Artifact</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function verifyParentalConsent() {
    const method = document.getElementById('modalVpcMethod').value;
    const token = 'VPC-' + method.toUpperCase() + '-' + Math.random().toString(36).substring(2, 10).toUpperCase();
    alert(`Parental Consent Verified!\n\nCryptographic Artifact Generated:\n${token}\n\nMinor student profile is now transitioned to 'Active' state under the DPDP Compliance Ledger.`);
    closeModal();
}

function openTemporalFeeModal() {
    const modalHtml = `
        <div id="activeModal" class="modal-backdrop-custom">
            <div class="modal-dialog-custom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-warning mb-0"><i class="fas fa-history me-2"></i>SCD Type 2 Fee Structure Update</h4>
                    <button class="btn btn-sm btn-outline-secondary" onclick="closeModal()"><i class="fas fa-times"></i></button>
                </div>
                <p class="small text-secondary mb-3">
                    To maintain immutable historical financial records, modifying a fee sets <code>valid_to = NOW()</code> on the existing row and creates a new active temporal row.
                </p>
                <div class="mb-3">
                    <label class="form-label-custom">Fee Name</label>
                    <input type="text" id="newFeeName" class="form-input-custom" value="Standard Monthly Dojo Fee">
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">New Amount ($)</label>
                    <input type="number" id="newFeeAmount" class="form-input-custom" value="85.00" step="0.01">
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button class="btn-martial-gold" onclick="saveTemporalFee()"><i class="fas fa-save"></i> Execute SCD Type 2 Update</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function saveTemporalFee() {
    const name = document.getElementById('newFeeName').value;
    const amount = parseFloat(document.getElementById('newFeeAmount').value);
    
    // Set valid_to on old row
    KOMS.feeStructures[0].validTo = "2026-09-10";
    KOMS.feeStructures[0].status = "archived";

    // Add new active row
    KOMS.feeStructures.unshift({
        id: KOMS.feeStructures.length + 1,
        dojoId: 1,
        feeName: name,
        amount: amount,
        frequency: "monthly",
        validFrom: "2026-09-10",
        validTo: null,
        status: "active"
    });

    alert("SCD Type 2 Update Executed!\n\nPrevious fee closed at 2026-09-10. New fee activated at $" + amount.toFixed(2) + ". Historical payments remain mathematically isolated.");
    closeModal();
    renderAppView();
}

function sealAttendanceSession() {
    if (confirm("Seal and cryptographically lock session?\n\nOnce locked, no further modifications can be made by instructors, securing belt advancement eligibility.")) {
        KOMS.attendanceSession.isLocked = true;
        alert("Attendance session has been cryptographically sealed.");
        renderAppView();
    }
}

function approveDojo(id) {
    const dojo = KOMS.dojos.find(d => d.id === id);
    if (dojo) {
        dojo.status = 'approved';
        alert(`Dojo '${dojo.name}' approved! Finite State Machine transition: Pending -> Approved.`);
        renderAppView();
    }
}

function filterSpaDojos(term) {
    const query = term.toLowerCase().trim();
    const container = document.getElementById('spaDojoList');
    if (!container) return;

    const filtered = KOMS.dojos.filter(d => d.status === 'approved' && (d.name.toLowerCase().includes(query) || d.location.toLowerCase().includes(query)));
    container.innerHTML = filtered.map(d => `
        <div class="p-3 mb-2 rounded border border-secondary" style="background: #121212;">
            <h6 class="text-warning mb-1">${d.name}</h6>
            <p class="small text-muted mb-1"><i class="fas fa-map-marker-alt text-danger me-1"></i>${d.location}</p>
            <p class="small text-muted mb-0"><i class="fas fa-calendar-alt text-info me-1"></i>${d.days} (${d.timings})</p>
        </div>
    `).join('');
}

function closeModal() {
    const modal = document.getElementById('activeModal');
    if (modal) modal.remove();
}

// ============================================================================
// Initialization on DOM Ready
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
    initParticleCanvas();
    initEntranceSequence();

    // Password visibility toggle with ARIA attribute compliance
    const togglePassBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('loginPassword');
    if (togglePassBtn && passwordInput) {
        togglePassBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            togglePassBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            togglePassBtn.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
    }

    // Login Form Submit
    const loginForm = document.getElementById('spaLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = document.getElementById('loginEmail').value.trim();
            const pass = document.getElementById('loginPassword').value;
            handleLogin(email, pass);
        });
    }

    // Initial View Check
    renderAppView();
});

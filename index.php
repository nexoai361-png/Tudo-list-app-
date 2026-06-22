<?php
/**
 * TaskMaster Pro - Professional Task Management & Calculator Suite
 * Powered by PHP & Vanilla JS (No External Dependencies)
 */
session_start();

// --- CONFIGURATION ---
define('DATA_FILE', 'tasks.json');
define('ADMIN_USER', 'admin@taskmaster.com');
define('ADMIN_PASS', 'password123'); // In a real app, use password_hash

// --- DATABASE SIMULATION (JSON) ---
function loadTasks() {
    if (!file_exists(DATA_FILE)) return [];
    return json_decode(file_get_contents(DATA_FILE), true) ?? [];
}

function saveTasks($tasks) {
    file_put_contents(DATA_FILE, json_encode($tasks, JSON_PRETTY_PRINT));
}

// --- API HANDLING ---
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    // Auth Check
    if (!isset($_SESSION['user']) && $_GET['api'] !== 'login') {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $action = $_GET['api'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'login':
            if (($input['email'] ?? '') === ADMIN_USER && ($input['password'] ?? '') === ADMIN_PASS) {
                $_SESSION['user'] = ADMIN_USER;
                echo json_encode(['success' => true]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
            break;

        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;

        case 'get_tasks':
            echo json_encode(loadTasks());
            break;

        case 'save_task':
            $tasks = loadTasks();
            $newTask = $input;
            $updated = false;
            foreach ($tasks as &$t) {
                if ($t['id'] === $newTask['id']) {
                    $t = $newTask;
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $tasks[] = $newTask;
            }
            saveTasks($tasks);
            echo json_encode(['success' => true]);
            break;

        case 'delete_task':
            $tasks = loadTasks();
            $tasks = array_values(array_filter($tasks, fn($t) => $t['id'] !== $input['id']));
            saveTasks($tasks);
            echo json_encode(['success' => true]);
            break;

        case 'reset':
            saveTasks([]);
            echo json_encode(['success' => true]);
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMaster Pro | Enterprise Edition</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1; --primary-dark: #4f46e5;
            --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --bg-page: #f8fafc; --bg-card: #ffffff;
            --text-main: #0f172a; --text-muted: #64748b;
            --border: #e2e8f0; --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius: 14px; --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --bg-page: #020617; --bg-card: #0f172a;
            --text-main: #f8fafc; --text-muted: #94a3b8;
            --border: #1e293b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-page); color: var(--text-main); line-height: 1.6; overflow-x: hidden; }
        button { cursor: pointer; border: none; background: none; font-family: inherit; transition: var(--transition); }
        input, select, textarea { width: 100%; padding: 12px 16px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--bg-card); color: var(--text-main); font-size: 1rem; }
        
        .hidden { display: none !important; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: 0.5rem; }
        .p-8 { padding: 2rem; }

        /* Login Screen */
        #login-screen {
            position: fixed; inset: 0; background: linear-gradient(135deg, #6366f1, #a855f7);
            display: flex; align-items: center; justify-content: center; z-index: 1000;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px);
            padding: 40px; border-radius: 28px; width: 100%; max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-align: center;
        }

        /* App Layout */
        #app-layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px; background: var(--bg-card); border-right: 1px solid var(--border);
            height: 100vh; position: sticky; top: 0; padding: 32px 24px;
            display: flex; flex-direction: column;
        }
        .logo { font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 12px; margin-bottom: 48px; color: var(--primary); }
        .nav-item {
            display: flex; align-items: center; gap: 12px; font-weight: 600; color: var(--text-muted);
            padding: 14px 20px; border-radius: 12px; margin-bottom: 4px; text-decoration: none;
        }
        .nav-item.active, .nav-item:hover { background: rgba(99, 102, 241, 0.08); color: var(--primary); }
        .nav-item svg { width: 20px; height: 20px; opacity: 0.8; }

        .main-content { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .header {
            height: 80px; background: var(--bg-card); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; padding: 0 40px;
            position: sticky; top: 0; z-index: 50;
        }

        /* Dashboard Bento */
        .grid { display: grid; gap: 24px; }
        .stats-grid { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 32px; }
        .card { background: var(--bg-card); border: 1px solid var(--border); padding: 24px; border-radius: 20px; box-shadow: var(--shadow); }
        .stat-label { color: var(--text-muted); font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; }
        .stat-value { font-size: 2rem; font-weight: 800; letter-spacing: -1px; }

        /* Task Module */
        .btn-plus { background: var(--primary); color: white; padding: 10px 20px; border-radius: 12px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .btn-plus:hover { background: var(--primary-dark); transform: scale(1.02); }
        
        .task-list { display: grid; gap: 16px; }
        .task-item {
            border: 1px solid var(--border); padding: 20px; border-radius: 16px;
            display: flex; gap: 16px; align-items: flex-start; transition: var(--transition);
        }
        .task-item:hover { border-color: var(--primary); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .checkbox {
            width: 24px; height: 24px; border-radius: 6px; border: 2px solid var(--border);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer;
        }
        .task-item.completed { opacity: 0.6; }
        .task-item.completed .checkbox { background: var(--success); border-color: var(--success); color: white; }
        .task-item.completed h3 { text-decoration: line-through; }
        .badge { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 99px; }
        .b-high { background: #fee2e2; color: #ef4444; }
        .b-med { background: #fef3c7; color: #d97706; }
        .b-low { background: #d1fae5; color: #059669; }

        /* Calculator */
        .calc-wrapper { display: grid; grid-template-columns: 1fr 340px; gap: 32px; max-width: 1100px; }
        .calc-box { padding: 32px; border-radius: 32px; }
        .calc-screen {
            background: var(--bg-page); padding: 32px; border-radius: 20px; text-align: right;
            margin-bottom: 24px; border: 1px solid var(--border);
        }
        .calc-val { font-size: 2.5rem; font-weight: 800; }
        .calc-btn-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .c-btn { height: 68px; border-radius: 16px; font-size: 1.25rem; font-weight: 700; background: var(--bg-page); color: var(--text-main); border: 1px solid var(--border); }
        .c-btn.op { color: var(--primary); background: rgba(99, 102, 241, 0.05); }
        .c-btn.eq { background: var(--primary); color: white; border-color: var(--primary); grid-column: span 2; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 200; }
        .modal-content { background: var(--bg-card); padding: 40px; border-radius: 28px; width: 100%; max-width: 500px; box-shadow: var(--shadow); position: relative; }

        /* Responsive */
        @media (max-width: 900px) {
            .sidebar { display: none; }
            .calc-wrapper { grid-template-columns: 1fr; }
            .header { padding: 0 20px; }
        }
    </style>
</head>
<body data-theme="light">

    <!-- ICONS -->
    <svg style="display: none;">
        <symbol id="icon-dash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></symbol>
        <symbol id="icon-task" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></symbol>
        <symbol id="icon-calc" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="16" y1="14" x2="16" y2="18"/><path d="M16 10h.01M12 10h.01M8 10h.01M12 14h.01M8 14h.01M12 18h.01M8 18h.01"/></symbol>
        <symbol id="icon-sett" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></symbol>
    </svg>

    <!-- LOGIN SCREEN -->
    <div id="login-screen" class="<?= isset($_SESSION['user']) ? 'hidden' : '' ?>">
        <div class="login-card">
            <h1 style="font-weight: 800; margin-bottom: 24px;">TaskMaster Pro</h1>
            <form id="auth-form">
                <input type="email" id="email" value="admin@taskmaster.com" required style="margin-bottom: 16px;" placeholder="Email">
                <input type="password" id="pass" value="password123" required style="margin-bottom: 24px;" placeholder="Password">
                <button type="submit" class="btn-plus" style="width: 100%; justify-content: center;">Sign In Professional</button>
            </form>
        </div>
    </div>

    <!-- MAIN APP -->
    <div id="app-layout" class="<?= !isset($_SESSION['user']) ? 'hidden' : '' ?>">
        <aside class="sidebar">
            <div class="logo">
                <svg width="28" height="28" stroke="currentColor"><use href="#icon-task"/></svg>
                TaskMaster
            </div>
            <nav style="flex: 1;">
                <a href="#" class="nav-item active" data-v="dash"><svg><use href="#icon-dash"/></svg> Dashboard</a>
                <a href="#" class="nav-item" data-v="tasks"><svg><use href="#icon-task"/></svg> Tasks</a>
                <a href="#" class="nav-item" data-v="calc"><svg><use href="#icon-calc"/></svg> Calculator</a>
                <a href="#" class="nav-item" data-v="sett"><svg><use href="#icon-sett"/></svg> Settings</a>
            </nav>
            <button class="nav-item" onclick="App.logout()" style="color: var(--danger); font-weight: 700;">Logout</button>
        </aside>

        <main class="main-content">
            <header class="header">
                <h2 id="view-title">Dashboard</h2>
                <div style="display: flex; gap: 16px; align-items: center;">
                    <button class="btn-plus" id="btn-new-task"> + Add Task</button>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800;">A</div>
                </div>
            </header>

            <div class="p-8">
                <!-- VIEW DASHBOARD -->
                <div id="v-dash" class="view">
                    <div class="grid stats-grid">
                        <div class="card"><div class="stat-label">Total Workload</div><div class="stat-value" id="stat-total">0</div></div>
                        <div class="card"><div class="stat-label">Completed</div><div class="stat-value" id="stat-done" style="color: var(--success);">0</div></div>
                        <div class="card"><div class="stat-label">Pending</div><div class="stat-value" id="stat-pen" style="color: var(--warning);">0</div></div>
                        <div class="card"><div class="stat-label">Success Rate</div><div class="stat-value" id="stat-pct">0%</div></div>
                    </div>
                </div>

                <!-- VIEW TASKS -->
                <div id="v-tasks" class="view hidden">
                    <div class="task-list" id="task-container"></div>
                </div>

                <!-- VIEW CALCULATOR -->
                <div id="v-calc" class="view hidden">
                    <div class="calc-wrapper">
                        <div class="card calc-box">
                            <div class="calc-screen">
                                <div id="c-pre" style="font-size: 0.875rem; color: var(--text-muted); min-height: 1.25rem;"></div>
                                <div id="c-cur" class="calc-val">0</div>
                            </div>
                            <div class="calc-btn-grid">
                                <button class="c-btn op" onclick="Calc.clear()">AC</button>
                                <button class="c-btn op" onclick="Calc.del()">DEL</button>
                                <button class="c-btn op" onclick="Calc.add('%')">%</button>
                                <button class="c-btn op" onclick="Calc.add('/')">÷</button>
                                <button class="c-btn" onclick="Calc.add('7')">7</button><button class="c-btn" onclick="Calc.add('8')">8</button><button class="c-btn" onclick="Calc.add('9')">9</button>
                                <button class="c-btn op" onclick="Calc.add('*')">×</button>
                                <button class="c-btn" onclick="Calc.add('4')">4</button><button class="c-btn" onclick="Calc.add('5')">5</button><button class="c-btn" onclick="Calc.add('6')">6</button>
                                <button class="c-btn op" onclick="Calc.add('-')">-</button>
                                <button class="c-btn" onclick="Calc.add('1')">1</button><button class="c-btn" onclick="Calc.add('2')">2</button><button class="c-btn" onclick="Calc.add('3')">3</button>
                                <button class="c-btn op" onclick="Calc.add('+')">+</button>
                                <button class="c-btn" onclick="Calc.add('0')">0</button><button class="c-btn" onclick="Calc.add('.')">.</button>
                                <button class="c-btn eq" onclick="Calc.eval()">=</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SETTINGS -->
                <div id="v-sett" class="view hidden">
                    <div class="card" style="max-width: 600px;">
                        <h3 style="margin-bottom: 24px;">Theme Settings</h3>
                        <div class="flex items-center justify-between">
                            <span>Dark Mode Interface</span>
                            <button onclick="App.toggleTheme()" id="th-toggle" style="width: 50px; height: 26px; border-radius: 99px; background: var(--border); position: relative;">
                                <div style="width: 20px; height: 20px; background: white; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: var(--transition);"></div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- NEW TASK MODAL -->
    <div class="modal-overlay" id="task-modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 20px;" id="m-title">Add New Project Task</h3>
            <form id="t-form">
                <input type="hidden" id="task-id">
                <div style="margin-bottom: 16px;">
                    <label style="display:block; font-size:0.875rem; font-weight:700; margin-bottom:8px;">Task Title</label>
                    <input type="text" id="t-title" required placeholder="Describe your goal">
                </div>
                <div class="grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 24px;">
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:700; margin-bottom:8px;">Priority</label>
                        <select id="t-pri">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:700; margin-bottom:8px;">Due Date</label>
                        <input type="date" id="t-date">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-plus" onclick="App.closeM()" style="background:var(--border); color:var(--text-main);">Cancel</button>
                    <button type="submit" class="btn-plus" style="flex: 1; justify-content: center;">Save Professional Task</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const App = {
            tasks: [],
            currV: 'dash',

            async init() {
                this.bind();
                if (document.getElementById('login-screen').classList.contains('hidden')) {
                    await this.loadTasks();
                }
            },

            bind() {
                // Auth
                document.getElementById('auth-form').onsubmit = async (e) => {
                    e.preventDefault();
                    const res = await fetch('?api=login', {
                        method: 'POST',
                        body: JSON.stringify({
                            email: document.getElementById('email').value,
                            password: document.getElementById('pass').value
                        })
                    });
                    if (res.ok) location.reload();
                    else alert('Access Denied: Invalid Credentials');
                };

                // Nav
                document.querySelectorAll('.nav-item[data-v]').forEach(el => {
                    el.onclick = (e) => {
                        e.preventDefault();
                        this.switchView(el.dataset.v);
                    };
                });

                // Tasks
                document.getElementById('btn-new-task').onclick = () => {
                    document.getElementById('t-form').reset();
                    document.getElementById('task-id').value = '';
                    document.getElementById('m-title').innerText = 'Add New Project Task';
                    document.getElementById('task-modal').style.display = 'flex';
                };

                document.getElementById('t-form').onsubmit = async (e) => {
                    e.preventDefault();
                    await this.saveTask();
                };
            },

            switchView(v) {
                document.querySelectorAll('.view').forEach(el => el.classList.add('hidden'));
                document.getElementById(`v-${v}`).classList.remove('hidden');
                document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
                document.querySelector(`.nav-item[data-v="${v}"]`)?.classList.add('active');
                document.getElementById('view-title').innerText = v.charAt(0).toUpperCase() + v.slice(1);
                this.currV = v;
                if (v === 'dash') this.renderStats();
                if (v === 'tasks') this.renderTasks();
            },

            async loadTasks() {
                const res = await fetch('?api=get_tasks');
                this.tasks = await res.json();
                this.renderStats();
            },

            async saveTask() {
                const task = {
                    id: document.getElementById('task-id').value || Date.now().toString(),
                    title: document.getElementById('t-title').value,
                    pri: document.getElementById('t-pri').value,
                    date: document.getElementById('t-date').value,
                    completed: false
                };
                await fetch('?api=save_task', { method: 'POST', body: JSON.stringify(task) });
                document.getElementById('task-modal').style.display = 'none';
                await this.loadTasks();
                if (this.currV === 'tasks') this.renderTasks();
            },

            async toggleTask(id) {
                const t = this.tasks.find(x => x.id === id);
                if (t) {
                    t.completed = !t.completed;
                    await fetch('?api=save_task', { method: 'POST', body: JSON.stringify(t) });
                    this.loadTasks();
                    if (this.currV === 'tasks') this.renderTasks();
                    if (this.currV === 'dash') this.renderStats();
                }
            },

            renderTasks() {
                const con = document.getElementById('task-container');
                con.innerHTML = this.tasks.map(t => `
                    <div class="task-item ${t.completed ? 'completed' : ''}">
                        <div class="checkbox" onclick="App.toggleTask('${t.id}')">${t.completed ? '✓' : ''}</div>
                        <div style="flex: 1;">
                            <h3 style="font-size: 1.1rem; margin-bottom: 4px;">${t.title}</h3>
                            <div class="flex items-center gap-2">
                                <span class="badge b-${t.pri}">${t.pri}</span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Due: ${t.date || 'TBD'}</span>
                            </div>
                        </div>
                        <button onclick="App.deleteTask('${t.id}')">🗑️</button>
                    </div>
                `).join('');
            },

            renderStats() {
                const tot = this.tasks.length;
                const done = this.tasks.filter(t => t.completed).length;
                document.getElementById('stat-total').innerText = tot;
                document.getElementById('stat-done').innerText = done;
                document.getElementById('stat-pen').innerText = tot - done;
                document.getElementById('stat-pct').innerText = tot ? Math.round((done/tot)*100) + '%' : '0%';
            },

            async deleteTask(id) {
                if (confirm('Delete professional task?')) {
                    await fetch('?api=delete_task', { method: 'POST', body: JSON.stringify({id}) });
                    await this.loadTasks();
                    if (this.currV === 'tasks') this.renderTasks();
                }
            },

            async logout() {
                await fetch('?api=logout');
                location.reload();
            },

            toggleTheme() {
                const isDark = document.body.getAttribute('data-theme') === 'dark';
                document.body.setAttribute('data-theme', isDark ? 'light' : 'dark');
                document.querySelector('#th-toggle div').style.left = isDark ? '3px' : '27px';
                document.getElementById('th-toggle').style.background = isDark ? 'var(--border)' : 'var(--primary)';
            },

            closeM() { document.getElementById('task-modal').style.display = 'none'; }
        };

        const Calc = {
            cur: '0', pre: '',
            add(v) {
                if (this.cur === '0' && v !== '.') this.cur = v;
                else this.cur += v;
                this.up();
            },
            clear() { this.cur = '0'; this.pre = ''; this.up(); },
            del() { this.cur = this.cur.length > 1 ? this.cur.slice(0, -1) : '0'; this.up(); },
            eval() {
                try {
                    const r = eval(this.cur.replace('×', '*').replace('÷', '/'));
                    this.pre = this.cur + ' =';
                    this.cur = r.toString();
                    this.up();
                } catch { this.cur = 'Error'; this.up(); }
            },
            up() {
                document.getElementById('c-cur').innerText = this.cur;
                document.getElementById('c-pre').innerText = this.pre;
            }
        };

        window.onload = () => App.init();
    </script>
</body>
</html>

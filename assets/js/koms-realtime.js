/**
 * MASS DRAGON DOJO - KOMS Real-Time Telemetry & Event Engine
 * Live EventSource SSE + Fast Polling Fallback, Martial Sound Synthesizer & Haptic Bridge
 */

(function() {
    'use strict';

    // Singleton state
    window.KomsRealtime = {
        status: 'disconnected', // 'connected', 'connecting', 'disconnected'
        lastEventId: 0,
        eventSource: null,
        pollTimer: null,
        soundEnabled: true,
        audioCtx: null,

        // Initialize Real-Time Connection
        init: function(options) {
            options = options || {};
            this.userId = options.userId || null;
            this.role = options.role || null;
            this.dojoId = options.dojoId || null;
            this.baseUrl = options.baseUrl || '';

            this.createStatusBar();
            this.initAudio();
            this.connect();

            // Handle visibility change (pause/resume on mobile sleep)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    if (this.status !== 'connected') {
                        this.connect();
                    }
                }
            });
        },

        // Setup audio context for martial chimes
        initAudio: function() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) {
                    this.audioCtx = new AudioCtx();
                }
            } catch (e) {
                // AudioContext not supported or restricted
            }
        },

        playMartialChime: function(frequency = 587.33, type = 'sine') { // D5 tone
            if (!this.soundEnabled || !this.audioCtx) return;
            try {
                if (this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume();
                }
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = type;
                osc.frequency.setValueAtTime(frequency, this.audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, this.audioCtx.currentTime + 0.15); // Ramp to A5

                gain.gain.setValueAtTime(0.3, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.45);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);

                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.5);
            } catch (e) {
                // Silent fail
            }
        },

        // Trigger mobile haptic feedback
        vibrate: function(ms = 200) {
            try {
                if (window.KomsNativeBridge && typeof window.KomsNativeBridge.vibrate === 'function') {
                    window.KomsNativeBridge.vibrate(ms);
                } else if (navigator.vibrate) {
                    navigator.vibrate(ms);
                }
            } catch (e) {}
        },

        // Inject UI Status Beacon into Header or Top Right
        createStatusBar: function() {
            if (document.getElementById('komsRealtimeBeacon')) return;

            const beacon = document.createElement('div');
            beacon.id = 'komsRealtimeBeacon';
            beacon.className = 'koms-realtime-beacon connecting';
            beacon.innerHTML = `
                <span class="beacon-pulse"></span>
                <span class="beacon-label">LIVE SYNC</span>
            `;

            // Inject styles if not present
            if (!document.getElementById('komsRealtimeStyles')) {
                const style = document.createElement('style');
                style.id = 'komsRealtimeStyles';
                style.textContent = `
                    .koms-realtime-beacon {
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        padding: 3px 9px;
                        border-radius: 20px;
                        font-size: 10px;
                        font-weight: 700;
                        letter-spacing: 0.8px;
                        text-transform: uppercase;
                        background: rgba(10, 10, 10, 0.75);
                        border: 1px solid rgba(255, 255, 255, 0.12);
                        color: #bbb;
                        transition: all 0.3s ease;
                        z-index: 1000;
                        cursor: pointer;
                    }
                    .koms-realtime-beacon .beacon-pulse {
                        width: 7px;
                        height: 7px;
                        border-radius: 50%;
                        background: #ffd21a;
                        box-shadow: 0 0 6px #ffd21a;
                        transition: all 0.3s ease;
                    }
                    .koms-realtime-beacon.connected {
                        border-color: rgba(34, 197, 94, 0.4);
                        color: #86efac;
                        background: rgba(5, 25, 12, 0.7);
                    }
                    .koms-realtime-beacon.connected .beacon-pulse {
                        background: #22c55e;
                        box-shadow: 0 0 10px #22c55e, 0 0 18px rgba(34, 197, 94, 0.6);
                        animation: beaconGlow 1.8s infinite;
                    }
                    .koms-realtime-beacon.connecting {
                        border-color: rgba(234, 179, 8, 0.4);
                        color: #fde047;
                    }
                    .koms-realtime-beacon.connecting .beacon-pulse {
                        background: #eab308;
                        box-shadow: 0 0 8px #eab308;
                        animation: beaconBlink 0.9s infinite;
                    }
                    @keyframes beaconGlow {
                        0%, 100% { opacity: 1; transform: scale(1); }
                        50% { opacity: 0.5; transform: scale(1.3); }
                    }
                    @keyframes beaconBlink {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0.2; }
                    }
                    .koms-toast-container {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: 99999;
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        max-width: 360px;
                        pointer-events: none;
                    }
                    @media (max-width: 600px) {
                        .koms-toast-container {
                            top: 12px;
                            left: 12px;
                            right: 12px;
                            max-width: none;
                        }
                    }
                    .koms-toast {
                        background: linear-gradient(135deg, #121212 0%, #1f0b0b 100%);
                        border: 1px solid rgba(255, 204, 0, 0.4);
                        border-left: 4px solid #ffd21a;
                        border-radius: 12px;
                        padding: 12px 15px;
                        color: #ffffff;
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.85);
                        display: flex;
                        align-items: flex-start;
                        gap: 12px;
                        pointer-events: auto;
                        animation: toastSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                    }
                    .koms-toast.attendance { border-left-color: #22c55e; }
                    .koms-toast.grading { border-left-color: #ffd21a; }
                    .koms-toast.payment { border-left-color: #38bdf8; }
                    .koms-toast-icon {
                        font-size: 18px;
                        line-height: 1;
                        padding-top: 2px;
                    }
                    .koms-toast-content { flex: 1; }
                    .koms-toast-title {
                        font-size: 13px;
                        font-weight: 700;
                        color: #ffd21a;
                        margin-bottom: 2px;
                    }
                    .koms-toast-body {
                        font-size: 12px;
                        color: #e5e5e5;
                        line-height: 1.35;
                    }
                    .koms-toast-close {
                        background: none;
                        border: none;
                        color: #777;
                        cursor: pointer;
                        font-size: 14px;
                        padding: 0;
                        line-height: 1;
                    }
                    @keyframes toastSlideIn {
                        0% { opacity: 0; transform: translateY(-20px) scale(0.95); }
                        100% { opacity: 1; transform: translateY(0) scale(1); }
                    }
                    @keyframes toastSlideOut {
                        0% { opacity: 1; transform: translateY(0); }
                        100% { opacity: 0; transform: translateY(-20px); }
                    }
                `;
                document.head.appendChild(style);
            }

            // Insert beacon into top navigation if available, else append fixed to top right
            const navContainer = document.querySelector('.koms-top-user, .koms-nav-user, .header-user, .navbar-nav, .student-nav-brand');
            if (navContainer) {
                navContainer.prepend(beacon);
            } else {
                beacon.style.position = 'fixed';
                beacon.style.top = '12px';
                beacon.style.right = '14px';
                document.body.appendChild(beacon);
            }

            // Toast Container
            if (!document.getElementById('komsToastContainer')) {
                const toastBox = document.createElement('div');
                toastBox.id = 'komsToastContainer';
                toastBox.className = 'koms-toast-container';
                document.body.appendChild(toastBox);
            }
        },

        updateStatus: function(status) {
            this.status = status;
            const beacon = document.getElementById('komsRealtimeBeacon');
            if (!beacon) return;

            beacon.className = 'koms-realtime-beacon ' + status;
            const label = beacon.querySelector('.beacon-label');
            if (label) {
                if (status === 'connected') label.textContent = 'LIVE SYNC';
                else if (status === 'connecting') label.textContent = 'CONNECTING...';
                else label.textContent = 'OFFLINE';
            }
        },

        // Main Connection Routine
        connect: function() {
            this.updateStatus('connecting');

            // Build query params
            const params = new URLSearchParams();
            if (this.userId) params.append('user_id', this.userId);
            if (this.role) params.append('role', this.role);
            if (this.dojoId) params.append('dojo_id', this.dojoId);
            if (this.lastEventId > 0) params.append('since_id', this.lastEventId);

            // Attempt Server-Sent Events (SSE)
            if (window.EventSource) {
                if (this.eventSource) {
                    this.eventSource.close();
                }

                const sseUrl = this.baseUrl + '/api/realtime/stream.php?' + params.toString();
                try {
                    this.eventSource = new EventSource(sseUrl);

                    this.eventSource.addEventListener('open', () => {
                        this.updateStatus('connected');
                    });

                    this.eventSource.addEventListener('handshake', (e) => {
                        this.updateStatus('connected');
                        try {
                            const data = JSON.parse(e.data);
                            if (data.last_id) this.lastEventId = data.last_id;
                        } catch (err) {}
                    });

                    // Event listeners for each event type
                    const eventTypes = [
                        'ATTENDANCE_MARKED', 'ATTENDANCE_LOCKED', 
                        'ANNOUNCEMENT_NEW', 'BELT_PROMOTED', 
                        'PAYMENT_RECEIVED', 'TOURNAMENT_UPDATE'
                    ];

                    eventTypes.forEach(type => {
                        this.eventSource.addEventListener(type, (e) => {
                            try {
                                const ev = JSON.parse(e.data);
                                if (ev.id && ev.id > this.lastEventId) {
                                    this.lastEventId = ev.id;
                                }
                                this.handleEvent(type, ev.payload || {});
                            } catch (err) {
                                console.error('Error parsing SSE event:', err);
                            }
                        });
                    });

                    this.eventSource.onerror = () => {
                        this.eventSource.close();
                        this.eventSource = null;
                        this.updateStatus('connecting');
                        // Fall back to polling
                        this.startPolling();
                    };

                    return;
                } catch (e) {
                    console.warn('SSE initialization failed, falling back to polling:', e);
                }
            }

            // Fallback: fast polling
            this.startPolling();
        },

        startPolling: function() {
            if (this.pollTimer) clearInterval(this.pollTimer);

            const poll = () => {
                const params = new URLSearchParams();
                if (this.userId) params.append('user_id', this.userId);
                if (this.role) params.append('role', this.role);
                if (this.dojoId) params.append('dojo_id', this.dojoId);
                params.append('since_id', this.lastEventId);

                fetch(this.baseUrl + '/api/realtime/poll.php?' + params.toString())
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.updateStatus('connected');
                            if (data.latest_id > this.lastEventId) {
                                this.lastEventId = data.latest_id;
                            }
                            if (data.events && data.events.length > 0) {
                                data.events.forEach(ev => {
                                    this.handleEvent(ev.type, ev.payload || {});
                                });
                            }
                        }
                    })
                    .catch(() => {
                        this.updateStatus('disconnected');
                    });
            };

            // Immediate initial poll
            poll();
            // Recurring poll every 3 seconds
            this.pollTimer = setInterval(poll, 3000);
        },

        // Dispatch & UI handlers
        handleEvent: function(type, payload) {
            this.playMartialChime();
            this.vibrate(200);

            switch (type) {
                case 'ATTENDANCE_MARKED':
                    this.showToast({
                        type: 'attendance',
                        icon: '🥋',
                        title: 'Attendance Marked!',
                        body: `Status: <strong>${(payload.status || 'Present').toUpperCase()}</strong> for ${payload.session_date || 'today'}. Marked by ${payload.marked_by || 'Sensei'}.`
                    });
                    this.updateAttendanceDOM(payload);
                    break;

                case 'ATTENDANCE_LOCKED':
                    this.showToast({
                        type: 'attendance',
                        icon: '🔒',
                        title: 'Session Sealed',
                        body: `Attendance for session #${payload.session_id} has been cryptographically sealed by Sensei.`
                    });
                    break;

                case 'ANNOUNCEMENT_NEW':
                    this.showToast({
                        type: 'announcement',
                        icon: '📢',
                        title: payload.title || 'New Dojo Announcement',
                        body: payload.content ? (payload.content.substring(0, 100) + '...') : 'A new broadcast has been posted.'
                    });
                    break;

                case 'BELT_PROMOTED':
                    this.vibrate([200, 100, 300]);
                    this.playMartialChime(784); // G5 victory chime
                    this.showToast({
                        type: 'grading',
                        icon: '🏆',
                        title: '🎉 BELT PROMOTION!',
                        body: `Congratulations! You have been promoted from ${payload.previous_belt || 'White'} to <strong>${payload.new_belt}</strong>!`
                    });
                    this.showBeltCelebration(payload);
                    break;

                case 'PAYMENT_RECEIVED':
                    this.showToast({
                        type: 'payment',
                        icon: '💰',
                        title: 'Payment Cleared',
                        body: `Received ₹${parseFloat(payload.amount_paid || 0).toFixed(2)} via ${payload.payment_method || 'Cash'}. Dues updated!`
                    });
                    break;
            }
        },

        // Dynamic DOM patching on live attendance
        updateAttendanceDOM: function(payload) {
            // Update today's attendance badge if on dashboard
            const badge = document.getElementById('todayAttendanceBadge') || document.querySelector('.today-attendance-pill');
            if (badge) {
                badge.className = 'badge bg-success text-uppercase';
                badge.innerHTML = `<i class="fas fa-check-circle me-1"></i> ${payload.status || 'Present'}`;
            }

            // If an attendance table exists, flash highlight
            const table = document.querySelector('.attendance-table');
            if (table) {
                table.style.transition = 'background 0.5s';
                table.style.backgroundColor = 'rgba(34, 197, 94, 0.15)';
                setTimeout(() => { table.style.backgroundColor = ''; }, 1200);
            }
        },

        // Visual Celebration Banner on Belt Promotion
        showBeltCelebration: function(payload) {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.85); z-index: 100000;
                display: flex; justify-content: center; align-items: center;
                animation: toastSlideIn 0.5s ease;
            `;
            modal.innerHTML = `
                <div style="background: #111; border: 2px solid #ffd21a; border-radius: 20px; padding: 30px; text-align: center; max-width: 380px; box-shadow: 0 0 50px rgba(255, 210, 26, 0.5);">
                    <div style="font-size: 50px; margin-bottom: 10px;">🥋</div>
                    <h2 style="color: #ffd21a; font-family: 'Caveat Brush', cursive; font-size: 32px; margin: 0 0 10px;">BELT PROMOTION!</h2>
                    <p style="color: #eee; font-size: 15px; margin-bottom: 15px;">You have achieved the rank of<br><strong style="font-size: 22px; color: #ffd21a;">${payload.new_belt}</strong></p>
                    <button style="background: linear-gradient(90deg, #9b0b0b, #e02323); border: none; color: white; padding: 10px 24px; border-radius: 8px; font-weight: bold; cursor: pointer;">Osu! Continue</button>
                </div>
            `;
            modal.querySelector('button').addEventListener('click', () => {
                modal.remove();
            });
            document.body.appendChild(modal);
        },

        // Floating Toast Notification
        showToast: function(opts) {
            const container = document.getElementById('komsToastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `koms-toast ${opts.type || ''}`;
            toast.innerHTML = `
                <div class="koms-toast-icon">${opts.icon || '🥋'}</div>
                <div class="koms-toast-content">
                    <div class="koms-toast-title">${opts.title}</div>
                    <div class="koms-toast-body">${opts.body}</div>
                </div>
                <button class="koms-toast-close">&times;</button>
            `;

            toast.querySelector('.koms-toast-close').addEventListener('click', () => {
                toast.style.animation = 'toastSlideOut 0.3s forwards';
                setTimeout(() => toast.remove(), 300);
            });

            container.appendChild(toast);

            // Auto dismiss after 6 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.animation = 'toastSlideOut 0.3s forwards';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 6000);
        }
    };

    // Auto-init on DOMContentLoaded if user session data is stamped
    document.addEventListener('DOMContentLoaded', () => {
        if (window.KOMS_SESSION && window.KOMS_SESSION.userId) {
            window.KomsRealtime.init({
                userId: window.KOMS_SESSION.userId,
                role: window.KOMS_SESSION.role,
                dojoId: window.KOMS_SESSION.dojoId,
                baseUrl: window.KOMS_SESSION.baseUrl || ''
            });
        }
    });

})();

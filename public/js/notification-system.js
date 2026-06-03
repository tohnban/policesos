/**
 * Notification System - Toast + Bell Drawer
 * Toast: Auto-dismissible pop-ups for critical notifications
 * Bell Drawer: Aggregated inbox with read/unread status
 */

class NotificationSystem {
    constructor(apiBaseUrl = '/api/v1') {
        this.apiBaseUrl = apiBaseUrl;
        this.unreadCount = 0;
        this.notifications = [];
        this.toastContainer = null;
        this.bellIcon = null;
        this.drawerOverlay = null;
        const menu = document.getElementById('notificationMenu');
        this.sessionFeedUrl = menu ? (menu.dataset.feedUrl || '') : '';
        this.sessionArchiveUrl = menu ? (menu.dataset.archiveUrl || '') : '';
        this.markAllReadUrl = menu
            ? ((document.getElementById('notificationReadForm') || {}).action || '')
            : '';
        this.init();
    }

    init() {
        this.createToastContainer();
        this.setupBellIcon();
        this.fetchUnreadCount();
        // Refresh unread count every 30 seconds
        setInterval(() => this.fetchUnreadCount(), 30000);
    }

    createToastContainer() {
        this.toastContainer = document.createElement('div');
        this.toastContainer.id = 'notification-toast-container';
        this.toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            pointer-events: none;
        `;
        document.body.appendChild(this.toastContainer);
    }

    setupBellIcon() {
        const header = document.querySelector('header') || document.querySelector('.navbar');
        if (!header) return;

        this.bellIcon = document.createElement('div');
        this.bellIcon.id = 'notification-bell';
        this.bellIcon.innerHTML = `
            <button id="notification-bell-btn" aria-label="Notificações" style="
                position: relative;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 24px;
                color: #333;
                padding: 8px 12px;
            ">
                🔔
                <span id="notification-badge" style="
                    position: absolute;
                    top: 0;
                    right: 0;
                    background: #e74c3c;
                    color: white;
                    border-radius: 50%;
                    width: 20px;
                    height: 20px;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: bold;
                "></span>
            </button>
        `;

        header.appendChild(this.bellIcon);
        document.getElementById('notification-bell-btn').addEventListener('click', () => this.toggleDrawer());
    }

    createDrawerOverlay() {
        if (this.drawerOverlay) return this.drawerOverlay;

        this.drawerOverlay = document.createElement('div');
        this.drawerOverlay.id = 'notification-drawer-overlay';
        this.drawerOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: none;
        `;
        this.drawerOverlay.addEventListener('click', () => this.toggleDrawer());

        const drawer = document.createElement('div');
        drawer.id = 'notification-drawer';
        drawer.style.cssText = `
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            max-width: 90vw;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2);
            z-index: 10001;
            display: flex;
            flex-direction: column;
            transition: right 0.3s ease-out;
        `;

        drawer.innerHTML = `
            <div style="padding: 16px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px;">Notificações</h3>
                <button id="notification-drawer-close" aria-label="Fechar" style="
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: #999;
                ">×</button>
            </div>
            <div id="notification-drawer-list" style="flex: 1; overflow-y: auto; padding: 8px;">
                <p style="text-align: center; color: #999; padding: 20px;">Carregando...</p>
            </div>
            <div style="padding: 12px; border-top: 1px solid #eee; display: flex; gap: 8px;">
                <button id="notification-mark-all-read" style="
                    flex: 1;
                    padding: 8px 12px;
                    background: #f0f0f0;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                ">Marcar como lidas</button>
                <a href="/notification/inbox" style="
                    flex: 1;
                    padding: 8px 12px;
                    background: #3498db;
                    color: white;
                    border-radius: 4px;
                    text-align: center;
                    text-decoration: none;
                    font-size: 12px;
                ">Ver tudo</a>
            </div>
        `;

        this.drawerOverlay.appendChild(drawer);
        document.body.appendChild(this.drawerOverlay);

        document.getElementById('notification-drawer-close').addEventListener('click', () => this.toggleDrawer());
        document.getElementById('notification-mark-all-read').addEventListener('click', () => this.markAllAsRead());

        return this.drawerOverlay;
    }

    toggleDrawer() {
        if (!this.drawerOverlay) {
            this.createDrawerOverlay();
        }

        const overlay = this.drawerOverlay;
        const drawer = document.getElementById('notification-drawer');
        const isOpen = overlay.style.display === 'flex';

        if (isOpen) {
            overlay.style.display = 'none';
            drawer.style.right = '-400px';
        } else {
            overlay.style.display = 'flex';
            drawer.style.right = '0';
            this.fetchNotificationsForDrawer();
        }
    }

    async fetchUnreadCount() {
        try {
            const token = this.getApiToken();
            if (token) {
                const response = await fetch(`${this.apiBaseUrl}/notifications?per_page=1`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();
                if (data.success) {
                    this.unreadCount = data.data.unread || 0;
                    this.updateBadge();
                }
                return;
            }

            if (!this.sessionFeedUrl) {
                return;
            }

            const response = await fetch(this.sessionFeedUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (data && typeof data.unread_count === 'number') {
                this.unreadCount = data.unread_count;
                this.updateBadge();
            }
        } catch (error) {
            console.error('Erro ao buscar notificações não lidas:', error);
        }
    }

    async fetchNotificationsForDrawer() {
        try {
            const token = this.getApiToken();
            if (token) {
                const response = await fetch(`${this.apiBaseUrl}/notifications?per_page=10`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();
                if (data.success) {
                    this.notifications = data.data.notifications || [];
                    this.renderDrawerList();
                }
                return;
            }

            if (!this.sessionFeedUrl) {
                return;
            }

            const response = await fetch(this.sessionFeedUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (data && Array.isArray(data.notifications)) {
                this.notifications = data.notifications;
                this.renderDrawerList();
            }
        } catch (error) {
            console.error('Erro ao carregar notificações:', error);
        }
    }

    renderDrawerList() {
        const list = document.getElementById('notification-drawer-list');
        if (!list) return;

        if (this.notifications.length === 0) {
            list.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Sem notificações</p>';
            return;
        }

        list.innerHTML = this.notifications.map((notif, index) => `
            <div style="
                padding: 12px;
                border-bottom: 1px solid #eee;
                background: ${notif.is_read ? '#fff' : '#f9f9f9'};
                opacity: ${notif.is_read ? 0.7 : 1};
                cursor: pointer;
                transition: background 0.2s;
            " data-id="${notif.id}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 13px; color: #333; margin-bottom: 4px;">
                            ${this.escapeHtml(notif.title || 'Notificação')}
                        </div>
                        <div style="font-size: 12px; color: #666; line-height: 1.4;">
                            ${this.escapeHtml((notif.message || '').substring(0, 60))}...
                        </div>
                        <div style="font-size: 11px; color: #999; margin-top: 4px;">
                            ${this.formatDate(notif.created_at)}
                        </div>
                    </div>
                    <button onclick="notificationSystem.archiveNotification(${notif.id})" 
                        style="
                            background: none;
                            border: none;
                            cursor: pointer;
                            color: #999;
                            font-size: 16px;
                            padding: 0;
                        " title="Arquivar">📦</button>
                </div>
            </div>
        `).join('');

        // Add click handlers to mark as read
        list.querySelectorAll('[data-id]').forEach(el => {
            el.addEventListener('click', (e) => {
                if (e.target.closest('button')) return; // Don't trigger on button click
                const id = el.getAttribute('data-id');
                this.markAsRead(id);
            });
        });
    }

    async markAsRead(notificationId) {
        try {
            const token = this.getApiToken();
            if (token) {
                await fetch(`${this.apiBaseUrl}/notifications`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_as_read',
                        notification_id: parseInt(notificationId, 10)
                    })
                });
            } else {
                const item = (this.notifications || []).find((n) => String(n.id) === String(notificationId));
                const readUrl = item && item.mark_read_url ? item.mark_read_url : '';
                if (!readUrl) {
                    return;
                }
                await this.postSessionForm(readUrl);
            }

            this.fetchNotificationsForDrawer();
            this.fetchUnreadCount();
        } catch (error) {
            console.error('Erro ao marcar como lido:', error);
        }
    }

    async markAllAsRead() {
        try {
            const token = this.getApiToken();
            if (token) {
                await fetch(`${this.apiBaseUrl}/notifications`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'mark_all_as_read' })
                });
            } else if (this.markAllReadUrl) {
                await this.postSessionForm(this.markAllReadUrl);
            } else {
                return;
            }

            this.fetchNotificationsForDrawer();
            this.fetchUnreadCount();
        } catch (error) {
            console.error('Erro ao marcar tudo como lido:', error);
        }
    }

    async archiveNotification(notificationId) {
        try {
            const token = this.getApiToken();
            if (token) {
                await fetch(`${this.apiBaseUrl}/notifications`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'archive',
                        notification_id: parseInt(notificationId, 10)
                    })
                });
            } else if (this.sessionArchiveUrl) {
                await this.postSessionForm(this.sessionArchiveUrl, {
                    notification_id: parseInt(notificationId, 10)
                });
            } else {
                return;
            }

            this.fetchNotificationsForDrawer();
        } catch (error) {
            console.error('Erro ao arquivar:', error);
        }
    }

    showToast(title, message, type = 'info', duration = 5000) {
        if (!this.toastContainer) return;

        const toast = document.createElement('div');
        const bgColor = {
            'success': '#27ae60',
            'error': '#e74c3c',
            'warning': '#f39c12',
            'info': '#3498db'
        }[type] || '#3498db';

        toast.style.cssText = `
            background: ${bgColor};
            color: white;
            padding: 16px 20px;
            border-radius: 4px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            pointer-events: auto;
            animation: slideInRight 0.3s ease-out;
            max-width: 360px;
        `;

        toast.innerHTML = `
            <div style="font-weight: 600; margin-bottom: 4px;">${this.escapeHtml(title)}</div>
            <div style="font-size: 14px; opacity: 0.95;">${this.escapeHtml(message)}</div>
        `;

        this.toastContainer.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out forwards';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    }

    updateBadge() {
        const badge = document.getElementById('notification-badge');
        if (!badge) return;

        if (this.unreadCount > 0) {
            badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    getApiToken() {
        return localStorage.getItem('api_token') || null;
    }

    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) {
            return meta.content;
        }
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    async postSessionForm(url, extraFields) {
        const body = new URLSearchParams();
        body.set('csrf_token', this.getCsrfToken());
        if (extraFields) {
            Object.keys(extraFields).forEach((key) => {
                body.set(key, String(extraFields[key]));
            });
        }
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: body.toString()
        });
        if (!response.ok) {
            throw new Error('Session notification action failed');
        }
        const payload = await response.json().catch(() => null);
        if (payload && payload.csrf_token) {
            document.querySelectorAll('input[name="csrf_token"]').forEach((input) => {
                input.value = payload.csrf_token;
            });
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) {
                meta.content = payload.csrf_token;
            }
        }
        return payload;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Agora';
        if (diffMins < 60) return `há ${diffMins}m`;
        if (diffHours < 24) return `há ${diffHours}h`;
        if (diffDays < 7) return `há ${diffDays}d`;

        return date.toLocaleDateString('pt-PT');
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize on page load if user is authenticated
document.addEventListener('DOMContentLoaded', () => {
    const isAuthenticated = document.getElementById('notificationMenu') !== null
        || document.querySelector('[data-user-id]') !== null;
    if (isAuthenticated) {
        window.notificationSystem = new NotificationSystem();
    }
});

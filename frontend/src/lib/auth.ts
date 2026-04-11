export interface AuthState {
    isLoggedIn: boolean;
    agentId: number | null;
}

let cached: AuthState | null = null;

export async function checkAuth(): Promise<AuthState> {
    if (cached) return cached;
    try {
        const res = await fetch('/api/agent/find?@select=id&user=EQ(@me)&@limit=1&status=GTE(-10)', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.ok) {
            const data = await res.json();
            const isLoggedIn = Array.isArray(data) && data.length > 0;
            cached = { isLoggedIn, agentId: isLoggedIn ? data[0].id : null };
        } else {
            cached = { isLoggedIn: false, agentId: null };
        }
    } catch {
        cached = { isLoggedIn: false, agentId: null };
    }
    return cached;
}

export function updateHeaderNav(state: AuthState): void {
    const panelLink = document.getElementById('nav-panel-link');
    const loginLink = document.getElementById('nav-login-link');
    if (panelLink) panelLink.hidden = !state.isLoggedIn;
    if (loginLink) loginLink.hidden = state.isLoggedIn;
}

export async function initAuth(): Promise<AuthState> {
    const state = await checkAuth();
    updateHeaderNav(state);
    return state;
}

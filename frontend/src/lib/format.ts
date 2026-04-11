/**
 * Formats a date value from the PHP API.
 * The API returns dates as ISO strings or { date: string } objects.
 */
export function formatDate(val: unknown, style: 'short' | 'long' = 'short'): string {
    if (!val) return '—';

    const str = typeof val === 'object' && val !== null && 'date' in val
        ? (val as { date: string }).date
        : String(val);

    const d = new Date(str);
    if (isNaN(d.getTime())) return '—';

    return style === 'long'
        ? d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' })
        : d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
}

export function formatDeadline(val: unknown): string {
    const formatted = formatDate(val);
    return formatted === '—' ? 'Prazo a confirmar' : `Até ${formatted}`;
}

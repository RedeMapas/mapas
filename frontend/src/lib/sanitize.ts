const TAGS = [
    'p', 'br', 'wbr', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'mark', 'small', 'del',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'ul', 'ol', 'li', 'a', 'blockquote', 'pre', 'code',
    'hr', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
    'img', 'figure', 'figcaption', 'div', 'span', 'section', 'article', 'main', 'aside',
    'dl', 'dt', 'dd', 'details', 'summary',
];

const TAG_RE = new RegExp(`<(\\/?)(${TAGS.join('|')})(\\s[^>]*)?>`, 'gi');

const DANGEROUS_ATTR_RE = /\s+(?:on\w+|style|formaction|xlink:href|data-bind|v-bind|ng-)[^=]*=[^>]*/gi;

const EVENT_HANDLER_RE = /\s+on\w+\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]*)/gi;

const HREF_RE = /\s+(?:href|src|action|poster|background)\s*=\s*(?:"([^"]*)"|'([^']*)')/gi;

function cleanHtml(html: string): string {
    let result = html;

    result = result.replace(/<script[\s\S]*?<\/script>/gi, '');
    result = result.replace(/<style[\s\S]*?<\/style>/gi, '');
    result = result.replace(/<link[\s\S]*?>/gi, '');
    result = result.replace(/<meta[\s\S]*?>/gi, '');
    result = result.replace(/<iframe[\s\S]*?<\/iframe>/gi, '');
    result = result.replace(/<object[\s\S]*?<\/object>/gi, '');
    result = result.replace(/<embed[\s\S]*?>/gi, '');
    result = result.replace(/<form[\s\S]*?<\/form>/gi, '');
    result = result.replace(/<base[\s\S]*?>/gi, '');

    result = result.replace(/<[^>]+>/g, (match) => {
        let tag = match;

        tag = tag.replace(EVENT_HANDLER_RE, '');
        tag = tag.replace(DANGEROUS_ATTR_RE, '');

        tag = tag.replace(HREF_RE, (attr, q1, q2) => {
            const val = (q1 ?? q2 ?? '').toLowerCase().trim();
            if (val.startsWith('javascript:') || val.startsWith('data:text/html') || val.startsWith('vbscript:')) {
                return '';
            }
            return attr;
        });

        return tag;
    });

    return result;
}

export function sanitizeHtml(html: string | null | undefined): string {
    if (!html || typeof html !== 'string') return '';
    return cleanHtml(html);
}

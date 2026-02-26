# Admin Checkbox List Styling Improvement Design

**Date:** 2026-02-26
**Component:** OpportunityWorkplan Admin Configuration
**Files Affected:** `src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css`

## Problem Statement

The current admin checkbox configuration interface for workplan fields has usability issues:
- **Spacing:** Groups feel cramped without clear visual separators
- **Hierarchy:** Sub-checkboxes (required/optional toggles) rely solely on 30px indentation to show parent-child relationship
- **Discoverability:** No hover states to help users understand interactivity
- **Visual clarity:** With 30+ checkbox groups, it's hard to scan and distinguish between main and sub-options

## Design Solution

**Approach:** Nested Cards with Left Border

This design uses a card-based treatment for sub-checkboxes with a distinctive left border accent, providing clear visual hierarchy and improved scannability.

---

## Visual Design Specifications

### Main Checkbox (Field Enable/Inform)

**Structure:**
- Current minimal styling preserved
- Represents "enable this field" or "inform this data"

**Styling:**
```css
.field__checkbox {
    padding: 12px 8px;
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s ease;
}
```

### Required Sub-Checkbox

**Structure:**
- Appears conditionally when main checkbox is enabled
- Represents "make this field required/mandatory"

**Styling:**
```css
.field__checkbox--sub {
    background: #f8f9fa;
    border-left: 3px solid #4a90e2;
    border-radius: 4px;
    padding: 10px 12px 10px 16px;
    margin: 8px 0 8px 30px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
    font-size: 0.9em;
    font-weight: 500;
    color: #495057;
}
```

### Typography

- **Main checkbox:** Current font-size (unchanged)
- **Sub-checkbox:**
  - Font-size: 0.9em (current)
  - Font-weight: 500 (increased from normal for better readability)
  - Color: #495057 (darker than current #666 for better contrast)

### Spacing Between Groups

```css
.field__group {
    margin-bottom: 16px; /* increased from 12px */
}

.field__group .field__checkbox--sub:last-child {
    margin-bottom: 0;
}
```

---

## Interactive States

### Main Checkbox Hover

```css
.field__checkbox:hover {
    background: #f8f9fa;
    cursor: pointer;
    transform: scale(1.005);
}
```

### Required Sub-Checkbox Hover

```css
.field__checkbox--sub:hover {
    background: #e9ecef;
    border-left-color: #357ABD;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    cursor: pointer;
}
```

### Focus States (Accessibility)

```css
.field__checkbox input:focus,
.field__checkbox--sub input:focus {
    outline: 2px solid #4a90e2;
    outline-offset: 2px;
}
```

### Disabled State

```css
.field__checkbox--sub:disabled,
.field__checkbox--sub.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
```

### Animations

**Entry Animation (when sub-checkbox appears):**
```css
@keyframes fadeInSlide {
    from {
        opacity: 0;
        transform: translateX(-4px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.field__checkbox--sub {
    animation: fadeInSlide 0.3s ease-in;
}
```

---

## Implementation Details

### CSS Variables (Theme Customization)

```css
:root {
    --checkbox-sub-bg: #f8f9fa;
    --checkbox-sub-border: #4a90e2;
    --checkbox-hover-bg: #e9ecef;
    --checkbox-separator: #e9ecef;
    --checkbox-text: #495057;
}
```

### Responsive Design

**Desktop (>768px):**
- Full design as specified
- Left margin: 30px
- Padding: 10px 12px 10px 16px
- Group spacing: 16px

**Tablet/Mobile (<768px):**
```css
@media (max-width: 768px) {
    .field__checkbox--sub {
        margin-left: 20px; /* reduced from 30px */
        padding: 8px 10px 8px 12px; /* reduced padding */
    }

    .field__group {
        margin-bottom: 12px; /* reduced from 16px */
    }
}
```

### Multiple Sub-Checkboxes

Some field groups have multiple sub-checkboxes (e.g., team composition with gender and race options):

```css
.field__checkbox--sub + .field__checkbox--sub {
    margin-top: 6px; /* spacing between sibling sub-checkboxes */
}
```

Both sub-checkboxes share the same left border color for visual grouping.

---

## Accessibility

- ✅ Maintain proper label-input associations (current structure preserved)
- ✅ Color contrast meets WCAG AA (4.5:1 minimum for text)
- ✅ Visible focus indicators for keyboard navigation
- ✅ Screen reader text unchanged (semantic HTML preserved)
- ✅ Hover states provide visual feedback
- ✅ Disabled states clearly communicated

---

## Files to Modify

1. **`/src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css`**
   - Update existing styles
   - Add new hover states
   - Add CSS variables
   - Add responsive breakpoints

2. **No template changes required** - HTML structure in `template.php` already has the correct classes

---

## Success Criteria

- ✅ Clear visual hierarchy between main and sub-checkboxes
- ✅ Improved scannability with visual separators
- ✅ Hover states provide clear interactive feedback
- ✅ Better spacing reduces visual clutter
- ✅ Accessible to keyboard and screen reader users
- ✅ Responsive design works on mobile/tablet
- ✅ Theme colors can be customized via CSS variables

---

## Alternative Approaches Considered

### Approach 2: Minimal with Icon Indicators
- Icons prefix main checkboxes, badges for sub-checkboxes
- **Rejected:** Icons don't translate well across languages, relies on subtle cues

### Approach 3: Indented Panel with Connecting Lines
- Tree-structure with visual connector lines
- **Rejected:** More complex CSS, could look heavy or old-fashioned

**Selected Approach 1** because it uses familiar design patterns (card treatment, left border accent), provides clear hierarchy, and balances modern aesthetics with functional clarity.

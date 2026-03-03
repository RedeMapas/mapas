# Admin Checkbox List Styling Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Improve admin checkbox configuration UI with better visual hierarchy, spacing, and hover states using nested card design with left border accents.

**Architecture:** CSS-only enhancement to existing checkbox list UI. No JavaScript or template changes required. Uses CSS variables for theme customization, card treatment for sub-checkboxes, and responsive breakpoints for mobile.

**Tech Stack:** CSS3, CSS Variables, Media Queries

**Design Doc:** `docs/plans/2026-02-26-admin-checkbox-styling-design.md`

---

## Task 1: Add CSS Variables for Theme Customization

**Files:**
- Modify: `src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css`

**Step 1: Read current CSS file**

Read the file to understand existing structure:

```bash
cat src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css
```

Expected: Current styles with basic sub-checkbox indentation and fadeIn animation

**Step 2: Add CSS variables at the top of the file**

Add after any existing comments, before any selectors:

```css
/* CSS Variables for theme customization */
:root {
    --checkbox-sub-bg: #f8f9fa;
    --checkbox-sub-border: #4a90e2;
    --checkbox-hover-bg: #e9ecef;
    --checkbox-separator: #e9ecef;
    --checkbox-text: #495057;
}
```

**Step 3: Verify CSS syntax**

Check for any syntax errors by opening the admin page in browser:
- Navigate to opportunity admin > workplan configuration
- Open browser DevTools > Console
- Expected: No CSS parsing errors

**Step 4: Commit CSS variables**

```bash
git add src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css
git commit -m "feat(workplan): add CSS variables for checkbox theming

Add CSS custom properties for checkbox colors, backgrounds, and borders
to enable easy theme customization.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 2: Update Main Checkbox Styles

**Files:**
- Modify: `src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css`

**Step 1: Add main checkbox base styles**

Add new `.field__checkbox` styles (this class should be added to labels in template if not present, but per design doc, template already has correct classes):

```css
/* Main checkbox styling */
.field__checkbox {
    padding: 12px 8px;
    border-bottom: 1px solid var(--checkbox-separator);
    transition: all 0.2s ease;
}
```

**Step 2: Add main checkbox hover state**

```css
.field__checkbox:hover {
    background: #f8f9fa;
    cursor: pointer;
    transform: scale(1.005);
}
```

**Step 3: Test main checkbox styles**

- Open admin workplan config page
- Hover over main checkboxes
- Expected: Slight background color change and subtle scale effect

**Step 4: Commit main checkbox styles**

```bash
git add src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css
git commit -m "feat(workplan): add main checkbox hover styles

Add padding, separator borders, and hover effects to main checkboxes
for better visual hierarchy and interactivity.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 3: Update Sub-Checkbox Card Treatment

**Files:**
- Modify: `src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css`

**Step 1: Replace existing `.field__checkbox--sub` styles**

Replace the current minimal styles with the new card treatment:

```css
/* Sub-checkbox card treatment */
.field__checkbox--sub {
    background: var(--checkbox-sub-bg);
    border-left: 3px solid var(--checkbox-sub-border);
    border-radius: 4px;
    padding: 10px 12px 10px 16px;
    margin: 8px 0 8px 30px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
    font-size: 0.9em;
    font-weight: 500;
    color: var(--checkbox-text);
}
```

**Step 2: Remove old sub-checkbox input styling if it exists**

Check if there's old `.field__checkbox--sub input[type="checkbox"]` styling and remove it (no longer needed).

**Step 3: Test sub-checkbox card appearance**

- Open admin workplan config
- Enable a main checkbox to reveal sub-checkbox
- Expected: Sub-checkbox appears in light gray card with blue left border

**Step 4: Commit sub-checkbox card styles**

```bash
git add src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css
git commit -m "feat(workplan): add card treatment to sub-checkboxes

Replace simple indentation with card-based design featuring:
- Light background color
- Blue left border accent
- Subtle shadow for depth
- Improved typography

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 4: Add Interactive Hover and Focus States

**Files:**
- Modify: `src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css`

**Step 1: Add sub-checkbox hover state**

```css
.field__checkbox--sub:hover {
    background: var(--checkbox-hover-bg);
    border-left-color: #357ABD;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    cursor: pointer;
}
```

**Step 2: Add focus states for accessibility**

```css
/* Focus states for keyboard navigation */
.field__checkbox input:focus,
.field__checkbox--sub input:focus {
    outline: 2px solid var(--checkbox-sub-border);
    outline-offset: 2px;
}
```

**Step 3: Add disabled state**

```css
.field__checkbox--sub:disabled,
.field__checkbox--sub.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
```

**Step 4: Test interactive states**

Manual testing:
- Hover over sub-checkboxes → expect darker background and shadow
- Tab through checkboxes with keyboard → expect visible focus outline
- Check disabled state (if applicable) → expect reduced opacity

**Step 5: Commit interactive states**

```bash
git add src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css
git commit -m "feat(workplan): add hover and focus states to checkboxes

Add interactive states:
- Hover: darker background and increased shadow
- Focus: visible outline for keyboard navigation
- Disabled: reduced opacity

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 5: Update Animation

**Files:**
- Modify: `src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css`

**Step 1: Replace existing fadeIn animation**

Find the existing `@keyframes fadeIn` and replace with improved version:

```css
/* Animation for sub-checkbox appearance */
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
```

**Step 2: Update animation reference in sub-checkbox**

Update the animation property in `.field__checkbox--sub`:

```css
.field__checkbox--sub {
    /* ... existing properties ... */
    animation: fadeInSlide 0.3s ease-in;
}
```

**Step 3: Remove old fadeIn animation**

Delete the old `@keyframes fadeIn` block if it exists separately.

**Step 4: Test animation**

- Toggle a main checkbox on/off
- Expected: Sub-checkbox slides in from left while fading in

**Step 5: Commit animation improvements**

```bash
git add src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css
git commit -m "feat(workplan): improve sub-checkbox appearance animation

Replace fadeIn with fadeInSlide for smoother entry effect.
Sub-checkboxes now slide in from left while fading in.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 6: Update Group Spacing

**Files:**
- Modify: `src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css`

**Step 1: Update field__group spacing**

Modify or add `.field__group` styles:

```css
/* Group spacing */
.field__group {
    margin-bottom: 16px;
}

.field__group .field__checkbox--sub:last-child {
    margin-bottom: 0;
}
```

**Step 2: Add spacing between sibling sub-checkboxes**

```css
/* Spacing between multiple sub-checkboxes */
.field__checkbox--sub + .field__checkbox--sub {
    margin-top: 6px;
}
```

**Step 3: Test spacing**

- Check spacing between checkbox groups (should be 16px)
- Find a group with multiple sub-checkboxes (e.g., team composition with gender + race)
- Expected: 6px gap between sibling sub-checkboxes

**Step 4: Commit spacing improvements**

```bash
git add src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css
git commit -m "feat(workplan): improve checkbox group spacing

Increase group spacing from 12px to 16px for better scannability.
Add 6px spacing between sibling sub-checkboxes.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 7: Add Responsive Breakpoints

**Files:**
- Modify: `src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css`

**Step 1: Add mobile/tablet responsive styles**

Add at the end of the file:

```css
/* Responsive design for mobile/tablet */
@media (max-width: 768px) {
    .field__checkbox--sub {
        margin-left: 20px;
        padding: 8px 10px 8px 12px;
    }

    .field__group {
        margin-bottom: 12px;
    }
}
```

**Step 2: Test responsive behavior**

Browser testing:
- Open DevTools and toggle device emulation
- Test at widths: 320px, 768px, 1024px
- Expected: Reduced margins and padding on mobile, full design on desktop

**Step 3: Commit responsive styles**

```bash
git add src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/styles.css
git commit -m "feat(workplan): add responsive styles for mobile/tablet

Reduce spacing and padding on screens <768px for better mobile UX.
Maintains visual hierarchy with left border indicator.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 8: Visual Testing and Verification

**Files:**
- Test: Manual visual testing in browser

**Step 1: Test all checkbox states**

Open workplan admin configuration and verify:

✅ Main checkbox hover shows background color
✅ Sub-checkbox appears with card treatment and left border
✅ Sub-checkbox hover darkens background and increases shadow
✅ Focus states visible when tabbing through checkboxes
✅ Animation smooth when toggling checkboxes
✅ Multiple sub-checkboxes have proper spacing
✅ Groups have clear visual separation

**Step 2: Test responsive behavior**

Resize browser or use device emulation:

✅ Desktop (>768px): Full design with 30px indent
✅ Mobile (<768px): Reduced spacing, 20px indent
✅ Layout doesn't break at any width

**Step 3: Test accessibility**

Keyboard navigation:
✅ Can tab through all checkboxes
✅ Focus indicator clearly visible
✅ Can activate checkboxes with Space/Enter

Color contrast (use browser DevTools):
✅ Text color #495057 on #f8f9fa background meets WCAG AA
✅ Border color visible

**Step 4: Cross-browser testing (if possible)**

Test in available browsers:
- Chrome/Edge (Chromium)
- Firefox
- Safari (if on Mac)

Expected: Consistent appearance across browsers

**Step 5: Document any issues**

If issues found, document them and fix before final commit. Otherwise, proceed to final commit.

---

## Task 9: Final Verification and Build

**Files:**
- Build: CSS compilation (if needed)

**Step 1: Check if CSS needs compilation**

Check if there's a build process:

```bash
# Check for SCSS/SASS files
ls src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/*.scss 2>/dev/null

# Check package.json for build commands
cat src/modules/OpportunityWorkplan/components/opportunity-enable-workplan/package.json 2>/dev/null
```

Expected: Plain CSS file, no build needed (based on .css extension)

**Step 2: Verify all changes are committed**

```bash
git status
```

Expected: Clean working tree

**Step 3: Review all commits**

```bash
git log --oneline -10
```

Expected: See all 7 feature commits for checkbox styling

**Step 4: Create summary commit message (if combining commits)**

Optional - if you want to squash commits:

```bash
# Only if you want a single commit
git rebase -i HEAD~7
# Mark all but first as 'squash'
```

**Step 5: Push changes (if on feature branch)**

```bash
git push origin feat/delivery-extended-fields
```

---

## Success Criteria Checklist

After implementation, verify:

- ✅ Clear visual hierarchy between main and sub-checkboxes (card + border)
- ✅ Improved scannability with visual separators (border-bottom on groups)
- ✅ Hover states provide clear interactive feedback (background change + shadow)
- ✅ Better spacing reduces visual clutter (16px group spacing)
- ✅ Accessible to keyboard and screen reader users (focus states, semantic HTML)
- ✅ Responsive design works on mobile/tablet (media query at 768px)
- ✅ Theme colors can be customized via CSS variables (:root variables)

---

## Rollback Plan

If styling causes issues:

```bash
# Revert all checkbox styling commits
git log --oneline --grep="workplan.*checkbox"
git revert <commit-hash>..HEAD

# Or reset to before changes
git reset --hard <commit-before-changes>
```

---

## Future Enhancements

Not in current scope, but possible improvements:

1. **Dark mode support** - Add dark theme CSS variables
2. **Transitions for expand/collapse** - Smooth height animation
3. **Checkbox status indicators** - Visual count of enabled fields
4. **Tooltips** - Explain what each field does
5. **Bulk actions** - Enable/disable multiple fields at once

---

## Notes for Implementation

- **No template changes needed** - HTML structure already correct
- **CSS only** - No JavaScript changes required
- **Backward compatible** - Existing functionality unchanged
- **Build process** - No compilation needed (plain CSS)
- **Browser support** - CSS variables work in all modern browsers
- **Testing** - Manual visual testing (no automated CSS tests)

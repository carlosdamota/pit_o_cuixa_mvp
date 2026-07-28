# Tasks: Fix Category Filter Bar Overflow

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~162 (1 deletion + ~79 line source replacement) |
| 400-line budget risk | Low — well under threshold |
| Chained PRs recommended | No — single trivial fix |
| Suggested split | Single PR |
| Delivery strategy | ask-always |
| Chain strategy | size-exception |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

## Phase 1: Core Fix

- [x] 1.1 Remove `min-width: max-content` from `.filter-bar__tabs` in `public/css/components/filter-bar.css` (line 82)
- [x] 1.2 Replace `src/frontend/css/components/filter-bar.css` with fixed deployed version (reconcile ~79-line structural diff)

## Phase 2: Verification

- [ ] 2.1 Manual visual check: `< 640px` viewport — tabs scroll internally, no page-level horizontal scroll
- [ ] 2.2 Manual visual check: `>= 640px` viewport — tabs centered, no scrollbar appears
- [ ] 2.3 Confirm `git diff --stat` shows only the two CSS files changed

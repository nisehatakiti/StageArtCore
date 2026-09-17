# StageArtCore Architecture

StageArtCore is the base WordPress plugin for StageArt sites. It owns members, productions, public production information, credits, surveys, and site settings.

Ticket reservation, reception, door tickets, and attribution-linked ticket management are intentionally implemented by StageArtTicket.

StageArtCore has no dependency on AuthCore.

## Content Management Principles

StageArt content is separated from layout and theme presentation.

- **Content** defines what is displayed.
- **Layout** defines where and in what order content is displayed.
- **Theme** defines how content is visually presented (colors, typography, backgrounds, spacing, cards, buttons, decoration, responsive presentation, etc.).
- A theme must not invent editorial copy, headings, catchphrases, CTA text, or other substantive content. If such content is required, it must be created as content, normally as a free-content item.

### Content ownership

Free content has an ownership scope that determines where it can be referenced.

- **TOP-owned free content** is shared content and can be referenced from the organization TOP and from every production.
- **Production-owned free content** belongs to one specific production and can be referenced only from that production.
- If the same free content is needed by multiple productions, the operational rule is to create it as TOP-owned free content and reference that shared item from each production.
- A production-owned free content item must not appear as a selectable candidate on the TOP or on another production.

The ownership scope is independent from the internal content type. Internal implementation fields such as `content_type`, `scope`, and `production_id` are implementation details and should not be exposed as user-facing terminology.

### Slug

Every free content item may have an internal Slug used for stable identification and internal/API references. Slug is not a user-facing selection label and should not be shown in the content-selection UI.

### Content categories in the selection UI

The layout editor must present content using human-understandable categories rather than technical labels such as "system" or "custom".

The default category grouping is:

- **【団体】** — organization-level content such as organization logo, organization information, representative message, and other organization content.
- **【公演】** — production-related content such as production list, production information, schedules, and individual production content that is valid in the current context.
- **【メンバー】** — member list and individual member content.
- **【お知らせ】** — notices/news and related content.
- **【自由コンテンツ】** — administrator-created content that does not belong to one of the predefined functional categories.

The category list must be extensible so future categories can be added without changing the fundamental selection model.

### Layout content selection rules

The selectable candidates are determined by the context being edited and must be filtered both in the UI and on the server side.

For the organization TOP, the editor may select:

- TOP-valid predefined content;
- TOP-owned free content.

For a production page, the editor may select:

- predefined content valid for the production context;
- TOP-owned free content;
- free content owned by the production currently being edited.

Content owned by another production must not be returned as a selectable candidate for the current production.

The UI should group candidates so that the administrator can immediately understand their purpose and scope. For example:

```text
【団体】
  劇団ロゴ
  劇団について
  代表メッセージ

【公演】
  公演一覧
  公演A
  公演B

【メンバー】
  メンバー一覧
  Aさん
  Bさん

【お知らせ】
  お知らせ一覧

【自由コンテンツ】
  劇団の歴史
  作品紹介
  演出家コメント
```

When editing a production, the same UI must expose TOP-owned free content as shared content and the current production's free content as production-specific content, while excluding other productions' content. Technical labels such as `system`, `custom`, `scope`, and `production_id` must not be used as the primary user-facing group names.

### Publication behavior for free content

StageArt free content is intended for immediate organization and layout use rather than a WordPress-style draft workflow.

- Newly created free content must be saved as **published immediately**.
- Creating free content must not default to or silently create a Draft state.
- A user may explicitly make content non-public later if that capability is provided.
- Non-public content must not be offered as a new selectable layout candidate.
- If content already placed in a layout becomes non-public, the administration UI should make that state visible and the public site must not render the non-public content.

The immediate-publication rule is part of the content-management contract and must be enforced by the content creation/update logic, not merely by a UI default.

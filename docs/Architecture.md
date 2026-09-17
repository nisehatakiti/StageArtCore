# StageArtCore Architecture

StageArtCore is the base WordPress plugin for StageArt sites. It owns members, productions, public production information, credits, surveys, and site settings.

Ticket reservation, reception, door tickets, and attribution-linked ticket management are intentionally implemented by StageArtTicket.

StageArtCore has no dependency on AuthCore.

## Content Management Principles

StageArt content is separated from layout and theme presentation.

- **Content** defines what is displayed.
- **Content blocks** define the reusable presentation unit used to display content.
- **Page layout** defines which blocks are placed on a page and in what order.
- **Theme** defines how blocks and content are visually presented (colors, typography, backgrounds, spacing, cards, buttons, decoration, responsive presentation, etc.).
- A theme must not invent editorial copy, headings, catchphrases, CTA text, or other substantive content. If such content is required, it must be created as content or as an explicit block setting, not silently invented by the theme.

The basic architecture is therefore:

```text
Page
  ↓
Page Layout
  ↓
Content Blocks
  ↓
Content / Block Settings
  ↓
Theme presentation
```

This separation is intended to make the same StageArt content reusable with different layouts and visual themes without implementing each organization's site as custom HTML.

### Content and content blocks

**Content** answers "what should be displayed?" Examples include organization information, next production, production archive, news, members, organization timeline, and free content.

**Content blocks** answer "as which reusable display component should it be displayed?" Examples include Hero, Feature, Link Card, List, Card Grid, and Timeline. A content block may reference existing content, or it may contain block-specific settings when that is intrinsic to the block.

For example:

```text
Link Card block
  ↓
Content: 公演アーカイブ
```

renders the selected content as a link card, while the same Link Card block can reference 「団体年表」 or 「団体について」.

A Hero block is a block-specific case. Its settings may include a background image, catch copy, supplementary text, and an optional link, because these values are part of the Hero presentation/content itself.

The initial block system is intentionally constrained to StageArt-oriented reusable components rather than becoming a general-purpose page builder. The exact block catalog and individual block settings are to be defined incrementally.

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

### Standard organization TOP content

StageArtCore provides standard organization-level content types as reusable building blocks. Providing a standard content type does not mean that it is automatically placed on the TOP; placement remains a page-layout decision.

The standard organization TOP content set includes:

- **【団体】** — organization logo, organization name, organization information, representative message, SNS, and contact information.
- **【公演】** — production list, next production, production archive, and organization timeline.
- **【メンバー】** — member list and member profiles.
- **【お知らせ】** — notice list and notices.
- **【アクセス】** — organization access information.
- **【自由コンテンツ】** — administrator-created content for organization-specific material that does not fit a predefined functional category.

Some standard content, such as production lists and member lists, is generated from StageArtCore's structured data rather than being manually maintained as duplicate content.

### Production lifecycle content

The production-related standard content distinguishes between upcoming productions, completed production archives, and the organization timeline.

- **Next production** is determined automatically from public productions whose relevant future date/time has not passed. When multiple applicable productions exist, the nearest upcoming production is selected.
- **Production archive** is determined automatically from the production's end date/time. A production moves into the archive view after its end date/time has passed; the underlying production record is not duplicated or moved between separate data stores.
- **Organization timeline** is a chronological history of the organization and is not a duplicate production archive. Production data is automatically included in the timeline, while non-production events are entered separately by an administrator.

### Production crown

A production may have an optional **公演冠** (production crown/prefix). It is stored as production data, independently of the production title.

The production title remains the WordPress production title. The crown is not appended to the stored title and is not included in the production Slug.

When a production is displayed as an automatically included item in the organization timeline/沿革, the display label is:

```text
公演冠＋「公演タイトル」（会場）
```

Examples:

```text
劇団○○ 第10回公演＋「タイトル」（○○劇場）
劇団○○ 企画公演＋「別タイトル」（駅前ホール）
「冠なしの公演」（○○劇場）
```

If no 公演冠 is configured, only the production title is used. If the venue is unavailable or not released, the venue portion is omitted rather than inventing text.

The timeline entry links directly to the existing production detail page. The production is not duplicated into a separate timeline record.

### Organization timeline

The organization timeline is a standard content type intended to represent the organization's history, activities, and milestones. Its display title is configurable by the administrator; the default title is **「団体年表」**. Possible presentation titles include 「沿革」 or 「私たちの歩み」.

A manually entered timeline event contains:

- **Start year** — required.
- **Start month** — required.
- **Start day** — optional.
- **End year** — optional as a whole.
- **End month** — required when an end date is specified.
- **End day** — optional.
- **Event** — required free text.

The end date is optional, but when an end date is entered, both end year and end month are required. End day may be omitted.

Productions are not entered manually as timeline events. Public production data is automatically integrated into the timeline. Production entries use the production's existing **公演冠＋「公演タイトル」（会場）** display form and link directly to the production detail page. There is no separate manual "related production" field.

The timeline merges manually entered events and automatically included productions into one chronological display.

#### Timeline date display rules

The timeline displays the year as a year heading. Within a year, the item date omits the year unless the displayed range crosses a calendar year.

Examples:

```text
Start: 2026/04       End: none       -> 4月
Start: 2026/04/29    End: none       -> 4月29日
Start: 2026/04       End: 2026/05   -> 4月～5月
Start: 2026/04/29    End: 2026/05   -> 4月29日～5月
Start: 2026/04/29    End: 2026/04/30 -> 4月29日～30日
Start: 2026/04/29    End: 2026/05/03 -> 4月29日～5月3日
```

If a range crosses a calendar year, the end year must be shown to avoid ambiguity.

#### Timeline sort rules

Timeline items are sorted automatically by their start date. The administrator can choose the display direction in the timeline settings.

The management-screen setting is:

- **Setting name:** `表示順`
- **Option:** `古い順（過去 → 現在）` — internal value `asc`
- **Option:** `新しい順（現在 → 過去）` — internal value `desc`
- **Default:** `古い順（過去 → 現在）`

The sort rules are:

1. Start year ascending or descending according to the selected display order.
2. Start month ascending or descending.
3. Within the same year/month, entries without a start day are placed before entries with a start day.
4. Entries with a start day are ordered by start day.
5. If the complete start date is identical, registration order is preserved.
6. The end date is never used as a sort key.

The absence of a start day does not mean that the event is assigned a fictitious calendar date; the ordering rule only determines its position within the same year/month.

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

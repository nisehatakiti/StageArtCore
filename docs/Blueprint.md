# StageArtCore Blueprint

## 1. Purpose

StageArtCore provides a reusable content-and-layout foundation for theatre and performing-arts organization sites.

The system separates:

```text
Page
  ↓
Content Blocks
  ↓
Content
  ↓
Theme presentation
```

A content block determines **where and how content is placed**. The theme determines visual presentation and must not invent editorial content.

## 2. Content Block Model

The organization TOP is assembled from ordered content blocks.

Each content block has:

- Block ID
- Optional heading
- Column count
- Ordered content slots
- Optional display direction
- Optional indentation per slot

The administrator selects the content for each slot from the content available in the current context.

### Column count

`columns` determines the number of columns used to arrange the block's contents on the public page.

Examples:

```text
1 column
┌──────────────────────┐
│ Content              │
└──────────────────────┘

2 columns
┌──────────┐ ┌──────────┐
│ Content  │ │ Content  │
└──────────┘ └──────────┘

3 columns
┌──────┐ ┌──────┐ ┌──────┐
│      │ │      │ │      │
└──────┘ └──────┘ └──────┘
```

The column count is a layout property of the block, not part of the content itself.

## 3. Content Placement

A block can contain multiple ordered content slots.

A slot can currently be:

- Content link
- Text heading

The selected content is rendered by the StageArt theme using the appropriate reusable presentation component.

The layout system should remain StageArt-oriented rather than becoming a general-purpose page builder.

## 4. Context and Content Selection

Content candidates must be filtered by context both in the UI and on the server.

For the organization TOP:

- TOP-valid standard content
- TOP-owned free content

For a production page:

- Production-valid standard content
- TOP-owned free content
- Free content owned by the current production

Content belonging to another production must not be selectable.

## 5. Hero Block

Hero is an independent content block.

Initial controls:

- Show / hide
- StageArt standard background preset
- User-selected WordPress media image

Standard Hero backgrounds are defined by:

`assets/hero/manifest.json`

The Hero background is selected independently from ordinary content placement.

Editorial copy is not invented by the theme. If catch copy or supplementary text is introduced, it is stored as Hero content/block settings.

## 6. Standard Organization TOP Content

The standard content catalog includes:

- 【団体】 organization logo, organization name, organization information, representative message, SNS, contact
- 【公演】 production list, next production, production archive, organization timeline
- 【メンバー】 member list, member profiles
- 【お知らせ】 notice list, notices
- 【アクセス】 access information
- 【自由コンテンツ】 administrator-created organization-specific content

Standard content is provided for selection but is not automatically placed merely because it exists.

## 7. Production Lifecycle

The same production record is used throughout its lifecycle.

- Next production: nearest applicable future public production
- Production archive: the same production after its end date/time
- Organization timeline: a separate chronological history that automatically includes public productions

No duplicate production record is created when a production becomes an archive item.

## 8. Theme Responsibility

The theme controls:

- Colors
- Typography
- Backgrounds
- Spacing
- Borders
- Cards and buttons
- Responsive presentation
- Block-specific visual treatment

The theme does not silently create:

- Organization marketing copy
- Catchphrases
- CTA wording
- Editorial headings

If such content is required, it belongs to content or explicit block settings.

## 9. Initial TOP Example

A typical organization TOP can be assembled as:

```text
Hero block

Feature block → 次回公演

List block → お知らせ

Link Card block → 公演アーカイブ
Link Card block → 団体について
Link Card block → 団体年表
Link Card block → お問い合わせ

Footer
```

The exact order and selected blocks remain an administrator layout decision.

## 10. Implementation Principle

Build the smallest reusable StageArt block system first.

Do not introduce a generic page-builder abstraction until a concrete StageArt requirement needs it.

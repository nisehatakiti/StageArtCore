# StageArt Theatre Theme

StageArtCoreの公演・メンバー・団体情報を劇団公式ホームページとして表示するクラシックテーマです。

## 方針

- StageArtCoreが公演・メンバー・団体情報の正本を保持します。
- Themeは表示・導線・レスポンシブデザインを担当します。
- StageArtTicketは別プラグインとして連携します。

## 主な表示

- トップページ：劇団名、紹介、公演、お知らせ
- 公演詳細：StageArtCore ProductionRouterの出力を共通デザインで表示
- メンバー一覧：`StageArt Members` テンプレート
- 連絡先：StageArtCoreが管理する連絡先ページ
- SNS：団体基本情報のSNS設定をフッターに表示

## インストール

GitHub Actionsで生成される `StageArtTheme` Artifact のZIPをWordPressの「外観 → テーマ → 新しいテーマを追加 → テーマのアップロード」からアップロードして有効化します。

# StageArtCore

舞台芸術団体向けのWordPress基盤プラグイン。

StageArtCoreは、公演・団体情報・メンバー・アンケートなど、StageArtサイトの基本機能を提供します。

## 構成

- Member: メンバー管理・公開ページ
- Production: 公演管理・公開ページ
- Survey: 公演アンケート
- Site Settings: サイト基本情報

## チケット管理

チケット予約・受付・当日券・誰扱い等のチケット管理機能は `StageArtTicket` に分離されています。

- StageArtCore: https://github.com/nisehatakiti/StageArtCore
- StageArtTicket: https://github.com/nisehatakiti/StageArtTicket
- AuthCore: https://github.com/nisehatakiti/AuthCore

StageArtCore自体はAuthCoreを必須としません。

## License

GPL v2 or later

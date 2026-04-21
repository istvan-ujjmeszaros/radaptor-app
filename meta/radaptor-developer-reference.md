# Radaptor Developer Reference

Standalone reference for the maintainer. Printable, no external dependencies.

---

## A) Fogalomtar (Glossary)

| Fogalom | Jelentes | Pelda path |
|---|---|---|
| **consumer app** | Alkalmazas, ami a framework/CMS csomagokat hasznalja. Ez a repo is az. | `radaptor-app/` |
| **first-party package** | A sajat framework, CMS es theme csomagok (4 db). Sajat GitHub repo-juk van, a fejlesztes a workspace-szintu `packages-dev/...` alatt tortenik. | framework, cms, portal-admin, so-admin |
| **packages-dev** | Workspace-szintu editable first-party package checkout-ok gyujtohelye. Minden csomagnak sajat nested Git repo-ja van itt. | `/apps/_RADAPTOR/packages-dev/core/framework/` |
| **registry mod** | Csomag a `packages/registry/...` alatti, verzio szerint letoltott, read-only masolatbol fut. Ez az alapertelmezett, committed allapot mind a 4 first-party csomagra. | `packages/registry/core/framework/` |
| **local override mod** | Maintainer-only runtime allapot: a gitignored `radaptor.local.json` atiranyitja a first-party csomag forrasat egy `packages-dev/...` alatti editable checkout-ra. Csak akkor ervenyes, ha a workspace package-dev compose override is aktiv. | `radaptor.local.json` + `radaptor.local.lock.json` |
| **canonical source / source of truth** | Az egyetlen hely, amit igaznak tekintunk ellentmondasnal. First-party package-eknel: a **csomag sajat GitHub repo-janak main branch-e**. | — |
| **nested repo** | Git repo egy masik Git repo-n belul (nem submodule). A `packages-dev/` alatti csomagok ilyenek. Csak a dedikalt kivetelek megengedettek (lasd AGENTS.md). | `/apps/_RADAPTOR/packages-dev/core/cms/.git` |
| **worktree** | `git worktree` paranccsal letrehozott parhuzamos checkout. Ugyanaz a repo, masik branch, masik konyvtar. Nem clone — megosztott `.git`. | — |
| **lockfile** | `radaptor.lock.json` — committed, registry-first. Local override aktiv allapotban a runtime `radaptor.local.lock.json`-t ir, a committed lock valtozatlan marad. | `radaptor.lock.json` / `radaptor.local.lock.json` |
| **baseline check** | `bin/check-repo-baseline.sh` — ellenorzi a repo hook konfiguraciot, a baseline profilt, es a php-cs-fixer formazast. A CI (GitHub Actions) is ezt futtatja. | `bin/check-repo-baseline.sh` |
| **local override guard** | `bin/check-local-override-state.sh` — a pre-commit hook resze. Megbukik, ha a committed manifest first-party dev forrast tartalmaz, ha `/workspace/packages-dev/` path-ok szivarognak a committed lockfile-ba, vagy ha a tracked generated fajlokban dev-path maradt. | `bin/check-local-override-state.sh` |

---

## B) Biztonsagi szabalyok AI-val valo fejleszteshez

### 1. Registry-first commit szabaly

A committed `radaptor.json` + `radaptor.lock.json` **mindig registry-first**.
First-party csomagot (framework/CMS/theme) szerkesztve nem a consumer app fajljait
modositod, hanem a workspace-szintu editable repo-t:

- `/apps/_RADAPTOR/packages-dev/core/framework/`
- `/apps/_RADAPTOR/packages-dev/core/cms/`
- `/apps/_RADAPTOR/packages-dev/themes/portal-admin/`
- `/apps/_RADAPTOR/packages-dev/themes/so-admin/`

Maintainer-local dev mod aktivalasa a consumer app-on beluli gitignored
`radaptor.local.json` + `radaptor.local.lock.json` fajlokkal tortenik. Runtime-hoz a
workspace package-dev compose override szukseges:

```
./bin/docker-compose-packages-dev.sh radaptor-app up -d --build
```

Worktree-eknek is registry-first kell maradniuk. Ha egy feature branch modul-valtozast
is igenyel, az a csomag sajat GitHub repo-jaban kulon PR, amit a `packages-dev/...`
alatt fejlesztunk es teszteltunk.

### 2. "Mi van, ha tevedek?" szabaly

Torlo/feluliro muvelet elott (rsync --delete, rm -rf, force-push, reset --hard):

```
git fetch && git diff origin/main
```

Ha a diff meglepetest tartalmaz: **STOP, jelezz a maintainernek**.
SOHA ne tekintsd az app-local tartalmat source of truth-nak a first-party package-eknel.

### 3. Merge sorrend

Egyszerre nyitott PR-ok batch-ben mergelhetok, **KIVEВЕ** ha:

- (a) ugyanazt a fajlt/repot modositjak
- (b) egymasra epulnek (pl. framework PR kell a CMS PR-hez)
- (c) schema/lockfile valtozas, amire a kovetkezo PR epit

Ilyenkor: egyenkent, sorrendben, minden merge utan ellenorzes.

Pelda: framework PR #10 + CMS PR #8 egymasra epul -> framework eloszor, proof, majd CMS.

### 4. Terv review szabaly

Ha egy kifejezes nem egyertelmu a tervben, az a **terv hibaja**, nem a tied.
Kerdezz ra. Az AI koteles konkret path-okat hasznalni absztrakt fogalmak helyett.

"Az editable package tartalom" **HELYETT**: "a `/apps/_RADAPTOR/packages-dev/core/framework/` konyvtar jelenlegi tartalma".

---

## Aktualis committed csomag allapot

A committed `radaptor.json` mind a 4 first-party csomagnal registry-first. Editable
first-party fejleszteshez gitignored `radaptor.local.json`-be kerul a `source.type =
"dev"` + logikai `location` (pl. `core/framework`), es a workspace package-dev compose
override-t kell hasznalni.

| Csomag | Committed mod | Registry path | Editable path (local override eseten) |
|---|---|---|---|
| core/framework | registry | `packages/registry/core/framework/` | `/apps/_RADAPTOR/packages-dev/core/framework/` |
| core/cms | registry | `packages/registry/core/cms/` | `/apps/_RADAPTOR/packages-dev/core/cms/` |
| themes/portal-admin | registry | `packages/registry/themes/portal-admin/` | `/apps/_RADAPTOR/packages-dev/themes/portal-admin/` |
| themes/so-admin | registry | `packages/registry/themes/so-admin/` | `/apps/_RADAPTOR/packages-dev/themes/so-admin/` |

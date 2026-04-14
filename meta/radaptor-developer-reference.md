# Radaptor Developer Reference

Standalone reference for the maintainer. Printable, no external dependencies.

---

## A) Fogalomtar (Glossary)

| Fogalom | Jelentes | Pelda path |
|---|---|---|
| **consumer app** | Alkalmazas, ami a framework/CMS csomagokat hasznalja. Ez a repo is az. | `radaptor-app/` |
| **first-party package** | A sajat framework, CMS es theme csomagok (4 db). Sajat GitHub repo-juk van, de a fejlesztes a consumer app-on belul tortenik. | framework, cms, portal-admin, so-admin |
| **dev mod** | Csomag a `packages/dev/...` alatti helyi Git checkout-bol fut, szerkesztheto. A fejlesztesi ciklus: dev modban dolgozol a csomagon -> PR a csomag GitHub repo-jaba -> merge -> uj verziokent publikalas -> consumer app frissitese registry modra. | `packages/dev/themes/so-admin/` |
| **registry mod** | Csomag a `packages/registry/...` alatti, verzio szerint letoltott, read-only masolatbol fut. Ez az alapertelmezett, production-safe allapot. | `packages/registry/core/framework/` |
| **app-local tartalom** | A `packages/dev/<type>/<id>/` konyvtar tartalma — a fajlok, amik a helyi gepen vannak. Lehet frissebb VAGY regebbi, mint a GitHub. | `packages/dev/core/framework/` |
| **canonical source / source of truth** | Az egyetlen hely, amit igaznak tekintunk ellentmondasnal. First-party package-eknel: a **csomag sajat GitHub repo-janak main branch-e**. | — |
| **nested repo** | Git repo egy masik Git repo-n belul (nem submodule). A `packages/dev/` alatti csomagok ilyenek. A szulo repo git parancsai nem latjak, `.git` konyvtara serulhet. | `packages/dev/core/cms/.git` |
| **worktree** | `git worktree` paranccsal letrehozott parhuzamos checkout. Ugyanaz a repo, masik branch, masik konyvtar. Nem clone — megosztott `.git`. | — |
| **lockfile** | `radaptor.lock.json` — rogziti, melyik csomag melyik verziobol/forrasbol fut. Reprodukalhatova teszi az install-t. | `radaptor.lock.json` |
| **baseline check** | `bin/check-repo-baseline.sh` — ellenorzi a repo hook konfiguraciot, a baseline profilt, es a php-cs-fixer formazast. A CI (GitHub Actions) is ezt futtatja. | `bin/check-repo-baseline.sh` |

---

## B) Biztonsagi szabalyok AI-val valo fejleszteshez

### 1. Worktree szabaly

Worktree-ben **tilos dev modulokat hasznalni** — csak registry csomagok futnak.
Modulba (framework/CMS/theme) nyulni **csak a fo working copy-ban** szabad (`radaptor-app/`).
Igy nincs conflict, ha ket branch parhuzamosan el.

Ha a feature branch-hez module valtozas is kell, az a module repo-ban kulon PR,
amit a fo working copy-ban fejlesztunk es tesztelunk.

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

"Az app-local tartalom" **HELYETT**: "a `packages/dev/core/framework/` konyvtar jelenlegi tartalma".

---

## Aktualis csomag allapot

| Csomag | Mod | Megjegyzes |
|---|---|---|
| core/framework | registry | `packages/registry/core/framework/` |
| core/cms | registry | `packages/registry/core/cms/` |
| themes/portal-admin | registry | `packages/registry/themes/portal-admin/` |
| themes/so-admin | dev | `packages/dev/themes/so-admin/` |

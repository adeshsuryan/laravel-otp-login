#!/bin/sh
# Create annotated tags <major>.<MINOR>.<PATCH> with illuminate constraints
# for that Laravel major. Defaults to .1.0. Does not modify or delete
# tags 0.1-5.13 or earlier modern tags.
set -eu

MINOR=${MINOR:-1}
PATCH=${PATCH:-0}
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "Working tree must be clean before release-tags." >&2
  exit 1
fi

BASE="$(git rev-parse HEAD)"
COMPOSER="$ROOT/composer.json"

write_composer() {
  major="$1"
  php_req="$2"
  illuminate="^${major}.0"
  python3 - "$COMPOSER" "$php_req" "$illuminate" <<'PY'
import json, sys
path, php_req, illuminate = sys.argv[1], sys.argv[2], sys.argv[3]
data = json.load(open(path))
pkgs = [
    "illuminate/auth",
    "illuminate/contracts",
    "illuminate/database",
    "illuminate/http",
    "illuminate/mail",
    "illuminate/routing",
    "illuminate/session",
    "illuminate/support",
]
data["require"] = {"php": php_req}
for p in pkgs:
    data["require"][p] = illuminate
# Dev tools stay flexible on the release commit
data["require-dev"] = {
    "orchestra/testbench": "^6.40|^7.40|^8.0|^9.0|^10.0|^11.0",
    "phpunit/phpunit": "^9.6|^10.5|^11.0|^12.0",
    "phpstan/phpstan": "^1.12|^2.0",
    "larastan/larastan": "^2.9|^3.0",
    "laravel/pint": "^1.0",
}
json.dump(data, open(path, "w"), indent=4)
open(path, "a").write("\n")
PY
}

restore_multi() {
  python3 - "$COMPOSER" <<'PY'
import json, sys
path = sys.argv[1]
data = json.load(open(path))
illuminate = "^8.0|^9.0|^10.0|^11.0|^12.0|^13.0"
pkgs = [
    "illuminate/auth",
    "illuminate/contracts",
    "illuminate/database",
    "illuminate/http",
    "illuminate/mail",
    "illuminate/routing",
    "illuminate/session",
    "illuminate/support",
]
data["require"] = {"php": "^8.0"}
for p in pkgs:
    data["require"][p] = illuminate
data["require-dev"] = {
    "orchestra/testbench": "^6.40|^7.40|^8.0|^9.0|^10.0|^11.0",
    "phpunit/phpunit": "^9.6|^10.5|^11.0|^12.0",
    "phpstan/phpstan": "^1.12|^2.0",
    "larastan/larastan": "^2.9|^3.0",
    "laravel/pint": "^1.0",
}
json.dump(data, open(path, "w"), indent=4)
open(path, "a").write("\n")
PY
}

for spec in \
  "8|^7.3|^8.0" \
  "9|^8.0" \
  "10|^8.1" \
  "11|^8.2" \
  "12|^8.2" \
  "13|^8.3"
do
  major=${spec%%|*}
  php_req=${spec#*|}
  tag="${major}.${MINOR}.${PATCH}"
  if git rev-parse "$tag" >/dev/null 2>&1; then
    echo "Tag $tag already exists � skip"
    continue
  fi
  git checkout -B "release/${tag}" "$BASE"
  write_composer "$major" "$php_req"
  git add composer.json
  git -c user.name="Adesh Suryan" -c user.email="adeshsuryan2005@gmail.com" \
    commit -m "release: ${tag} for Laravel ${major}"
  git tag -a "$tag" -m "Laravel OTP login ${tag} (Laravel ${major})"
  echo "Created $tag"
done

git checkout master 2>/dev/null || git checkout main
restore_multi
git add composer.json
if ! git diff --cached --quiet; then
  git -c user.name="Adesh Suryan" -c user.email="adeshsuryan2005@gmail.com" \
    commit -m "chore: restore multi-major illuminate constraints on default branch"
fi

echo "Done. Push with: git push origin master && git push origin 8.0.0 9.0.0 10.0.0 11.0.0 12.0.0 13.0.0"

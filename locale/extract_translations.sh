#!/bin/bash
set -euo pipefail

POTFILE="messages.pot"
echo "Extracting translatable strings from all PHP files..."

xgettext \
    --from-code=UTF-8 \
    --language=PHP \
    --keyword=_ \
    --keyword=gettext \
    --add-comments=TRANSLATORS \
    --output="$POTFILE" \
    --package-name="RaspAP" \
    --package-version="$(git describe --tags --abbrev=0 2>/dev/null || echo 'dev')" \
    --msgid-bugs-address="Bill Zimmerman <billzimmerman@gmail.com>" \
    --copyright-holder="RaspAP contributors" \
    --no-wrap \
    $(find .. -name "*.php" \
        -not -path "./locale/*" \
        -not -path "./.git/*" \
        -not -path "./plugins/*/.git/*" 2>/dev/null || true)

echo "Generated/updated $POTFILE"

echo "Merging new strings into all existing .po files..."
for po in */LC_MESSAGES/messages.po; do
    if [[ -f "$po" ]]; then
        echo "   → $po"
        msgmerge --update --backup=off --no-fuzzy-matching "$po" "$POTFILE"
    else
        echo "   → $po (not found, skipping)"
    fi
done

echo ""
echo "Extraction complete"
echo "   • New strings are now in locale/messages.pot"
echo "   • All .po files have been updated (translations preserved)"
echo "   • Run: pocompile.sh  to rebuild .mo files"
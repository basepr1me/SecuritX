#/bin/sh

echo '.dump' | sqlite3 ../securitx.db > tmp.dump
sed -i 's/phone INTEGER/phone TEXT/g' tmp.dump
mv ../securitx.db ../securitx.db.bak
cat tmp.dump | sqlite3 ../securitx.db
rm tmp.dump
doas chown www:www ../securitx.db

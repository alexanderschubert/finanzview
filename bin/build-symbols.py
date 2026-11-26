#!/usr/bin/env python3
"""
Baut public/symbols/lucide.json aus dem npm-Paket lucide-static.

    npm pack lucide-static && tar xzf lucide-static-*.tgz
    python3 bin/build-symbols.py package

Das Argument ist der entpackte Paketordner (mit icon-nodes.json, tags.json, LICENSE).
Die Suchbegriffe werden um deutsche Wörter ergänzt (Tabelle unten).
"""
import json, shutil, sys
from pathlib import Path

src = Path(sys.argv[1] if len(sys.argv) > 1 else 'package')
out = Path(__file__).resolve().parent.parent / 'public' / 'symbols'

nodes = json.loads((src / 'icon-nodes.json').read_text())
tags = json.loads((src / 'tags.json').read_text())
version = json.loads((src / 'package.json').read_text())['version']

# Englischer Suchbegriff -> deutsche Wörter (kleingeschrieben, ohne Umlaut-Zwang: die Suche normalisiert).
DE = {
    'house': 'haus wohnung wohnen miete', 'home': 'haus zuhause wohnen', 'building': 'gebäude haus', 'key': 'schlüssel',
    'car': 'auto pkw fahrzeug', 'fuel': 'tanken benzin sprit', 'gas': 'gas tanken', 'bus': 'bus nahverkehr', 'train': 'zug bahn',
    'bike': 'fahrrad rad', 'bicycle': 'fahrrad rad', 'plane': 'flugzeug flug', 'airplane': 'flugzeug flug', 'ship': 'schiff fähre',
    'parking': 'parken parkplatz', 'taxi': 'taxi', 'motorcycle': 'motorrad', 'scooter': 'roller',
    'shopping': 'einkaufen shopping einkauf', 'cart': 'warenkorb einkaufswagen supermarkt', 'basket': 'korb einkauf', 'store': 'laden geschäft',
    'shop': 'laden geschäft', 'bag': 'tasche einkaufstasche', 'gift': 'geschenk', 'package': 'paket',
    'utensils': 'essen restaurant besteck', 'coffee': 'kaffee café', 'pizza': 'pizza', 'beer': 'bier', 'wine': 'wein', 'cake': 'kuchen torte geburtstag',
    'apple': 'apfel obst', 'carrot': 'gemüse karotte', 'fish': 'fisch', 'milk': 'milch', 'cookie': 'keks', 'egg': 'ei', 'salad': 'salat',
    'sandwich': 'brot belegt', 'ice': 'eis', 'cup': 'tasse becher', 'soup': 'suppe', 'beef': 'fleisch', 'croissant': 'bäckerei',
    'heart': 'herz gesundheit', 'pill': 'medikament tablette apotheke', 'stethoscope': 'arzt', 'hospital': 'krankenhaus klinik', 'tooth': 'zahn zahnarzt',
    'glasses': 'brille', 'activity': 'aktivität puls', 'dumbbell': 'sport fitness training hantel', 'bandage': 'pflaster',
    'baby': 'baby kind', 'dog': 'hund haustier', 'cat': 'katze haustier', 'paw': 'tier haustier pfote', 'graduation': 'bildung abschluss schule',
    'school': 'schule', 'book': 'buch lesen', 'backpack': 'rucksack schule', 'shirt': 'kleidung hemd', 'hanger': 'kleiderbügel kleidung',
    'wallet': 'geldbeutel portemonnaie', 'banknote': 'geld schein', 'coins': 'münzen geld', 'credit': 'kreditkarte', 'card': 'karte kreditkarte',
    'piggy': 'sparschwein sparen', 'bank': 'bank', 'landmark': 'bank gebäude', 'receipt': 'beleg rechnung quittung', 'calculator': 'rechner',
    'chart': 'diagramm statistik', 'trending': 'trend entwicklung', 'percent': 'prozent zins', 'euro': 'euro', 'dollar': 'dollar',
    'briefcase': 'arbeit beruf job koffer', 'shield': 'versicherung schutz', 'lock': 'sicherheit schloss', 'scale': 'waage recht',
    'wifi': 'internet wlan', 'phone': 'telefon handy', 'smartphone': 'handy smartphone', 'laptop': 'computer laptop', 'monitor': 'bildschirm computer',
    'tv': 'fernseher tv', 'router': 'router internet', 'zap': 'strom energie blitz', 'lightbulb': 'licht glühbirne strom', 'plug': 'stecker strom',
    'flame': 'heizung feuer gas', 'droplet': 'wasser tropfen', 'droplets': 'wasser', 'thermometer': 'temperatur heizung', 'trash': 'müll abfall',
    'recycle': 'recycling müll', 'broom': 'putzen reinigung', 'wrench': 'werkzeug reparatur', 'hammer': 'werkzeug handwerk', 'tool': 'werkzeug',
    'sofa': 'sofa möbel', 'bed': 'bett schlafen', 'bath': 'bad badewanne', 'shower': 'dusche bad', 'washing': 'waschen wäsche',
    'film': 'film kino', 'clapperboard': 'film kino', 'music': 'musik', 'headphones': 'kopfhörer musik', 'gamepad': 'spiele konsole',
    'game': 'spiel', 'ticket': 'ticket karte eintritt', 'camera': 'foto kamera', 'palette': 'malen kunst farbe', 'theater': 'theater',
    'tent': 'zelt camping', 'mountain': 'berg wandern', 'umbrella': 'regenschirm strand urlaub', 'sun': 'sonne', 'beach': 'strand urlaub',
    'luggage': 'koffer reise', 'map': 'karte reise', 'compass': 'kompass reise', 'globe': 'welt erde', 'hotel': 'hotel übernachtung',
    'calendar': 'kalender termin datum', 'clock': 'uhr zeit', 'timer': 'zeit timer', 'bell': 'erinnerung glocke', 'mail': 'post brief email',
    'envelope': 'brief post', 'stamp': 'briefmarke post', 'file': 'datei dokument', 'folder': 'ordner', 'tag': 'etikett kategorie', 'tags': 'etiketten kategorien',
    'star': 'stern favorit', 'flag': 'flagge', 'target': 'ziel', 'rocket': 'rakete start', 'sparkles': 'glanz neu', 'leaf': 'blatt natur pflanze',
    'trees': 'bäume natur', 'tree': 'baum natur', 'flower': 'blume garten', 'sprout': 'pflanze garten', 'tractor': 'landwirtschaft',
    'scissors': 'schere friseur', 'user': 'person benutzer', 'users': 'personen familie gruppe', 'handshake': 'vereinbarung hilfe', 'church': 'kirche spende',
    'hand': 'hand', 'heartbeat': 'gesundheit puls', 'cigarette': 'rauchen zigarette', 'candy': 'süßigkeiten', 'popcorn': 'kino popcorn',
    'newspaper': 'zeitung zeitschrift', 'subscription': 'abo', 'repeat': 'wiederholen abo', 'refresh': 'aktualisieren', 'undo': 'rückgängig erstattung',
    'download': 'herunterladen', 'upload': 'hochladen', 'cloud': 'wolke cloud', 'server': 'server', 'printer': 'drucker', 'pen': 'stift schreiben',
    'paintbrush': 'malen pinsel', 'hard': 'hart', 'archive': 'archiv', 'box': 'karton kiste', 'boxes': 'kartons umzug', 'truck': 'lieferwagen lkw umzug',
    'warehouse': 'lager', 'factory': 'fabrik', 'hourglass': 'sanduhr', 'swords': 'kampf', 'trophy': 'pokal sieg', 'medal': 'medaille',
}

def norm(s):
    return s.lower()

icons = {}
for name, shapes in sorted(nodes.items()):
    words = list(tags.get(name, []))
    words += name.split('-')
    extra = []
    for w in set(words):
        for key, de in DE.items():
            if key == w or (len(key) > 4 and key in w):
                extra.append(de)
    text = ' '.join(dict.fromkeys(w for w in words[:14] + ' '.join(extra).split()))
    icons[name] = {'n': [[tag, attrs] for tag, attrs in shapes], 't': norm(text)}

out.mkdir(parents=True, exist_ok=True)
(out / 'lucide.json').write_text(json.dumps({'version': version, 'icons': icons}, ensure_ascii=False, separators=(',', ':')))
shutil.copy(src / 'LICENSE', out / 'LICENSE-lucide.txt')
print(len(icons), 'Symbole,', (out / 'lucide.json').stat().st_size // 1024, 'KB')

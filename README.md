# FinanzView

FinanzView ist eine moderne, selbst gehostete Finanzverwaltung zur Verwaltung von Konten, Buchungen, Kategorien, Budgets und wiederkehrenden Ausgaben.

Das Projekt wird als Webanwendung betrieben und ist für den privaten bzw. persönlichen Finanzüberblick ausgelegt.

## 🚀 Funktionen

### 💳 Konten

- Verwaltung mehrerer Konten
- Kontostände und Kontobewegungen
- Zuordnung von Buchungen zu Konten
- Übersicht über Einnahmen und Ausgaben

### 💸 Transaktionen

- Einnahmen und Ausgaben erfassen
- Buchungsdatum
- Beschreibung
- Händler
- Kategorie
- Konto
- Notizen
- Referenz
- Wiederkehrende Buchungen
- Ausstehende Buchungen
- Bearbeiten und Löschen von Buchungen

### 🗂️ Kategorien

- Eigene Kategorien erstellen
- Kategorien bearbeiten und löschen
- Icons für Kategorien
- Zuordnung von Buchungen zu Kategorien

### 🎯 Budgets

FinanzView unterstützt verschiedene Budgetarten:

- Monatliche Budgets
- Jährliche Budgets
- Benutzerdefinierte Budgets
- Start- und Enddatum
- Budgetbetrag
- Frei wählbare Kategorien
- Individuelle Farben und Icons
- Anzeige des aktuellen Verbrauchs
- Verbleibender Betrag
- Prozentualer Verbrauch
- Erkennung von Budgetüberschreitungen
- Zugehörige Buchungen innerhalb des Budgetzeitraums

Die Berechnung der Budgets erfolgt zentral über den `BudgetService`, damit Dashboard, Budgetübersicht und Budgetdetails dieselbe Berechnungslogik verwenden.

### 📊 Dashboard

Das Dashboard soll einen schnellen Überblick über die persönliche finanzielle Situation ermöglichen.

Geplante bzw. vorhandene Informationen:

- Kontostände
- Einnahmen
- Ausgaben
- Budgets
- Budgetverbrauch
- aktuelle Buchungen
- finanzielle Entwicklungen

---

## 🛠️ Technologie

FinanzView basiert auf modernen Open-Source-Technologien.

### Backend

- PHP
- Laravel
- Eloquent ORM
- Laravel Blade

### Frontend

- Blade Templates
- Tailwind CSS
- JavaScript

### Datenbank

Die Anwendung ist für relationale Datenbanken ausgelegt.

Aktuell wird eine SQL-basierte Datenbank verwendet.

### Betrieb

FinanzView kann containerisiert betrieben werden und eignet sich dadurch besonders für einen eigenen Server, NAS oder Homelab.

---

## 📁 Projektstruktur

Eine vereinfachte Struktur des Projekts:

```text
finanzblick/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   └── Services/
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   └── views/
│       ├── budgets/
│       ├── transactions/
│       ├── accounts/
│       └── ...
│
├── routes/
│   └── web.php
│
├── storage/
│
├── tests/
│
├── artisan
├── composer.json
└── README.md
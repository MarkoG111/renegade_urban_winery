# 🍷 Renegade Urban Winery – Event Ticketing System

## 📑 Table of Contents

- [Overview](#overview)
- [Problem](#problem)
- [Solution](#solution)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Key Features](#key-features)
- [Data Model](#data-model)
- [System Flow](#system-flow)
- [Core Concepts](#core-concepts)
- [API Endpoints](#api-endpoints)
- [Admin Panel](#admin-panel)
- [Real-Time Event Stats](#real-time-event-stats)
- [Project Structure](#project-structure)
- [Setup Guide](#setup-guide)
- [System Preview](#system-preview)
- [Demo](#demo)
- [What This Project Demonstrates](#what-this-project-demonstrates)

---

<a id="overview"></a>
## 📌 Overview

Renegade Urban Winery is a production-ready event ticketing system built by extending WooCommerce into a fully functional event management platform.

Instead of building infrastructure from scratch, the system leverages WordPress for content and WooCommerce for payments, while introducing a custom backend layer for ticket generation, validation, and real-time event tracking.

The result is a system capable of handling real-world event logistics including secure ticket distribution, fraud prevention, and check-in management.

---

<a id="problem"></a>
## 🎯 Problem

Traditional WooCommerce setups are not designed for:
- Event-based seat allocation
- Secure ticket validation
- Preventing duplicate ticket usage
- Real-time check-in tracking

This project solves those limitations by introducing a custom ticketing engine on top of WooCommerce.

---

<a id="solution"></a>
## 💡 Solution

A custom backend layer was introduced that handles:
- Ticket generation
- Seat allocation
- QR validation
- Check-in tracking

---

<a id="architecture"></a>
## 🧠 Architecture

The system is divided into 3 layers:

**1. WordPress (Base Layer)**
- Content management (events)
- Admin interface

**2. WooCommerce (E-commerce Layer)**
- Payments via Stripe
- Order lifecycle
- Emails

**3. Custom Backend (Core Logic)**
- Ticket generation
- Seat allocation (transactions)
- QR validation
- REST API

---

<a id="tech-stack"></a>
## 🛠 Tech Stack

![WordPress](https://img.shields.io/badge/WordPress-21759B?style=for-the-badge&logo=wordpress&logoColor=white)
![WooCommerce](https://img.shields.io/badge/WooCommerce-96588A?style=for-the-badge&logo=woocommerce&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Elementor](https://img.shields.io/badge/Elementor-92003B?style=for-the-badge&logo=elementor&logoColor=white)
![Stripe](https://img.shields.io/badge/Stripe-Payments-6772E5?style=for-the-badge&logo=stripe&logoColor=white)
![Dompdf](https://img.shields.io/badge/Dompdf-PDF--Gen-blue?style=for-the-badge&logo=adobeacrobatreader&logoColor=white)
![html5-qrcode](https://img.shields.io/badge/html5--qrcode-QR--Scanner-success?style=for-the-badge&logo=qrcode&logoColor=white)

---

<a id="key-features"></a>
## 🚀 Key Features

### 🎟 Automated Ticket Generation
- Unique ticket ID generation (`RW-XXXXXXX`)
- Automatic seat assignment per event
- Ticket creation triggered on WooCommerce order completion
- Multiple tickets per order supported

### 📄 PDF Ticket System
- Dynamic PDF generation using Dompdf
- Each ticket includes event details, seat number, and a unique QR code
- Automatically attached to the confirmation email

### 📱 QR Code Validation System

Each ticket contains a secure QR code encoding the `ticket_id` and a cryptographic HMAC hash.

Validation flow:
1. Scan QR code
2. Call REST API endpoint
3. Verify hash integrity
4. Check ticket status
5. Mark ticket as **used**

Prevents duplicate entry and forged tickets.

### 🔐 Secure Ticket Verification
- Hash-based validation using `hash_hmac`
- Prevents manual ticket ID manipulation
- Stateless verification via REST API

---

<a id="data-model"></a>
## 🗂 Data Model

Custom database tables:

| Table | Purpose |
|---|---|
| `event_tickets` | Stores all generated tickets and their status |
| `event_checkins` | Records each check-in event |
| `event_seat_counter` | Tracks seat allocation per event |

Custom tables were chosen for better performance, transaction support, and relational structure.

---

<a id="system-flow"></a>
## ⚙️ System Flow

```
1. User purchases ticket via WooCommerce
2. Order is completed
3. System generates:
   ├── Unique ticket ID  (RW-XXXXXXXX)
   ├── Seat number       (atomic allocation)
   ├── HMAC hash         (security layer)
   └── PDF Ticket
4. PDF ticket is emailed to the user
5. At the event:
   ├── QR code is scanned
   ├── REST API validates ticket + hash
   ├── Ticket is marked as used
   └── Check-in is recorded
```

---

<a id="core-concepts"></a>
## 🧩 Core Concepts

### Ticket ID
```
Format:    RW-XXXXXXXX
Non-sequential, generated via wp_generate_password
```

### Seat Allocation
Race conditions are prevented using database transactions with row locking:
```sql
SELECT ... FOR UPDATE
```

### QR Security
Each QR code encodes:
```
ticket_id + HMAC hash
hash_hmac('sha256', ticket_id, secret)
```

### Atomic Validation
Ticket consumption is a single atomic operation:
```sql
UPDATE tickets
SET status = 'used'
WHERE ticket_id = ? AND status = 'valid'
```

---

<a id="api-endpoints"></a>
## 🔌 API Endpoints

### Verify Ticket
```
GET /wp-json/rw-tickets/v1/verify
```

| Param | Type | Description |
|---|---|---|
| `ticket` | string | The ticket ID |
| `hash` | string | HMAC hash from QR code |

**Response:** `valid` / `used` / `invalid`

### Event Stats
```
GET /wp-json/rw-tickets/v1/stats
```

Returns tickets sold, check-ins, and remaining capacity.

---

<a id="admin-panel"></a>
## 📊 Admin Panel

Custom admin panel for managing events and tickets:

- **Event overview** with live statistics
- **Tracking:** tickets sold, check-ins, remaining capacity
- **Manual ticket controls:** cancel / reset / delete a ticket
- **QR Scanner** built with `html5-qrcode` — works directly in browser, no external device required

---

<a id="real-time-event-stats"></a>
## 🔄 Real-Time Event Stats

- Live ticket statistics via REST API
- Auto-refresh every 3 seconds
- Displays: total sold, checked-in users, remaining tickets

---

<a id="project-structure"></a>
## 📁 Project Structure

```text
```text
wp-content/
│
├── plugins/
│   ├── rw-tickets/                  # CORE SYSTEM (ticket generation, validation, seat allocation, REST API)
│   │   └── tickets.php
│   │
│   ├── my-events-plugin/            # AJAX filtering logic for events (category, status, pagination)
│   │   └── my-events-plugin.php
│   │   └── event-filter.js
│   │
│   ├── advanced-custom-fields/      # Event metadata (date, location, product mapping)
│   ├── woocommerce/                 # E-commerce engine (orders, checkout, cart)
│   ├── woocommerce-gateway-stripe/  # Stripe payment integration
│   ├── custom-post-type-ui/         # UI for registering Event CPT and taxonomies
│   ├── updraftplus/                 # Backup and restore system for WordPress
│   ├── wp-mail-smtp/                # SMTP email configuration (ensures ticket email delivery)
│   ├── elementor/                   # Page builder used for layout and UI composition
│
└── themes/
    └── blocksy-child/
        ├── functions.php            # Theme hooks, WooCommerce overrides, ticket system integration
        ├── archive-event.php        # Event listing page (uses filtering, pagination, event cards)
        ├── single-event.php         # Single event page (details, gallery, purchase entry point)
        ├── scanner.js               # QR scanner frontend logic (admin ticket validation)
        ├── event-gallery.js         # Gallery interactions (lightbox, navigation, animations)
        ├── css/
           ├── events/               # Event pages styling (cards, layout, filters)
           ├── woocommerce/          # Custom WooCommerce styling (checkout, cart, UI tweaks)
           ├── style.css             # Global theme styles
           └── tickets.css           # Ticket-related UI (scanner, validation states)
        ├── template-parts/
           ├── event-card.php        # Reusable event card component (used in listings) 
           └── event-gallery.php     # Dynamic gallery rendering + lightbox system 

```

---

<a id="setup-guide"></a>
## ⚙️ Setup Guide

### Requirements

- PHP 8+
- MySQL
- WordPress (local or server)
- Composer (optional)

### 1. Install WordPress

Download WordPress and place the project inside `wp-content/`.

### 2. Activate Plugins

- WooCommerce
- Advanced Custom Fields
- Custom Post Type UI *(if used)*
- `rw-tickets` *(custom)*
- `my-events-plugin` *(custom)*

### 3. Configure WooCommerce

Set up Stripe payments, create a ticket product, and enable stock management.

### 4. Create an Event (CPT)

Using ACF, fill in:
- `event_date`
- `location`
- `woo_product` *(links product → event)*

### 5. Enable Pretty Permalinks

Go to **WordPress Settings → Permalinks** and set to `/post-name/`.

Required for the `/verify-ticket/` route to work.

### 6. Run Database Setup

Tables are auto-created via `register_activation_hook()` on plugin activation:
- `event_tickets`
- `event_checkins`
- `event_seat_counter`

---

<a id="system-preview"></a>
## 📸 System Preview

### QR Validation

**Valid ticket** – marked and consumed:

<img width="429" alt="Valid ticket scan" src="https://github.com/user-attachments/assets/1c69bcf7-6569-4990-92a2-ff1bd98fb9d3" />

**Used ticket** – rejected:

<img width="431" alt="Used ticket rejected" src="https://github.com/user-attachments/assets/b531e5df-d0c6-4082-b463-734fc8979d25" />

### Admin Dashboard

**Event overview and ticket statistics:**

<img width="575" alt="Admin dashboard stats" src="https://github.com/user-attachments/assets/135142fd-057f-4a98-b7e8-35717a6afe6a" />

**Manual ticket controls:**

<img width="1722" alt="Admin manual controls" src="https://github.com/user-attachments/assets/e8d0c46a-4682-4b50-bdb0-46e34b11fab3" />

### Email System

**Automatic ticket delivery with PDF attachment:**

<img width="431" alt="Confirmation email" src="https://github.com/user-attachments/assets/2ae0ce20-dc0e-4861-a1b7-b0f77237a25e" />

<img width="525" alt="PDF ticket with QR code" src="https://github.com/user-attachments/assets/e87a96a9-ca10-4bb9-9046-7610c89ee3b4" />

---

<a id="demo"></a>
## 🎥 Demo

| | |
|---|---|
| 🎬 Full System Overview | https://www.youtube.com/watch?v=LSsF2rVEQ9k |
| 🛒 Ticket Purchase Flow | https://www.youtube.com/watch?v=TJwqHD2TgBI |
| 🌐 Live Demo | https://renegade-winary.infinityfreeapp.com/ |
| 📄 PDF Documentation | https://renegade-winary.infinityfreeapp.com/Dokumentacija.pdf |

---

<a id="what-this-project-demonstrates"></a>
## 🧠 What This Project Demonstrates

- Extending WordPress beyond a CMS into a custom application platform
- Designing custom data models with transaction support
- Building REST APIs inside WordPress
- Handling real-world backend logic: tickets, validation, concurrency
- Integrating multiple systems: payments, email, QR scanning, admin tools

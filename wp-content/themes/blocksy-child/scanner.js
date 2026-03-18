let isProcessing = false;
let scanner;

function startScanner() {
    scanner = new Html5QrcodeScanner("reader", {
        fps: 10,
        qrbox: 250
    });

    scanner.render(onScanSuccess);
}

async function onScanSuccess(decodedText) {
    if (isProcessing) return;
    isProcessing = true;

    const el = document.getElementById("result");
    el.innerHTML = "Checking ticket...";

    try {
        const url = new URL(decodedText);
        const ticket = url.searchParams.get("ticket");
        const hash = url.searchParams.get("hash");

        if (!ticket || !hash) {
            throw new Error("Invalid QR code");
        }

        const apiUrl =
            `${rwTickets.restUrl}?ticket=${encodeURIComponent(ticket)}&hash=${encodeURIComponent(hash)}`;

        const res = await fetch(apiUrl);

        const data = await res.json();

        showResult(data);
    } catch (e) {
        el.innerHTML = `<div class="rw-invalid">Invalid QR code</div>`;
    } finally {
        setTimeout(() => {
            isProcessing = false;
        }, 2500);
    }
}

function showResult(data) {
    const el = document.getElementById("result");
    const status = (data.status || "").toLowerCase();

    if (status === "valid") {
        el.innerHTML = `
            <div class="rw-valid">
                <div class="rw-icon">✓</div>
                <div class="rw-title">VALID TICKET</div>
                <div class="rw-event">${data.event}</div>
                <div class="rw-seat">Seat ${data.seat}</div>
            </div>
        `;

    } else if (status === "used") {
        el.innerHTML = `
            <div class="rw-used">
                <div class="rw-icon">⚠</div>
                <div class="rw-title">ALREADY USED</div>
            </div>
        `;

    } else {
        el.innerHTML = `
            <div class="rw-invalid">
                <div class="rw-icon">✕</div>
                <div class="rw-title">INVALID TICKET</div>
            </div>
        `;
    }

    setTimeout(() => {
        el.innerHTML = "Ready to scan ticket";
        isProcessing = false;
    }, 2000);
}

function restartScanner() {
    location.reload();
}

window.addEventListener("load", startScanner);

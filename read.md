# Store Pickup PDF Ticket Implementation

This plan outlines the addition of a PDF ticket generation feature specifically for "Store Pickup" orders. Unlike standard shipping orders, customers selecting "Store Pickup" will receive a downloadable/printable PDF containing their verification code and order details to present at the physical terminal.

## Proposed Changes

### 1. External Dependencies
#### [MODIFY] `checkout.php`
- Inject the `html2pdf.js` library via CDN (e.g., `cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js`) into the `<head>` or before the closing `</body>` tag.

### 2. PDF Template UI
#### [MODIFY] `checkout.php`
- Create a visually hidden HTML structure (`<div id="pickupTicketTemplate" style="display: none;">`) that acts as the template for the PDF.
- The template will include:
  - The Maroc PC logo and "Store Pickup Authorization" header.
  - A unique, randomly generated Verification Code (e.g., `PICKUP-A94F-88B2`).
  - The selected store's name, address, and operating hours.
  - The customer's order summary and total amount.
  - A placeholder barcode or aesthetic QR pattern to simulate a scannable terminal ticket.

### 3. Order Processing Logic
#### [MODIFY] `assets/js/checkout.js`
- Intercept the final checkout "Place Order" completion logic.
- Check if the selected shipping method is `pickup`.
- If `pickup`:
  - Generate the `Verification Code` and inject the store details into the hidden `#pickupTicketTemplate`.
  - Trigger `html2pdf().from(document.getElementById('pickupTicketTemplate')).save('MarocPC-Pickup-Ticket.pdf')`.
  - Display a success modal informing the user to save their ticket.

## User Review Required

> [!IMPORTANT]
> **Client-Side vs Server-Side PDF:** This plan proposes generating the PDF on the **client side** using `html2pdf.js`. This is fast, requires no PHP backend configuration (like mPDF/TCPDF), and relies entirely on HTML/CSS for styling the ticket. Please confirm if client-side generation is acceptable.

> [!NOTE]
> Do you want the PDF to automatically download immediately after clicking "Place Order", or would you prefer a "Download Ticket" button to appear on a success page/modal?

## Verification Plan

### Manual Verification
1. Proceed to checkout and select "Store Pickup".
2. Select a city/store on the interactive map.
3. Complete the checkout form (simulated).
4. Verify that `MarocPC-Pickup-Ticket.pdf` is successfully generated and downloaded.
5. Open the PDF to confirm styling, verification code presence, and correct store location details match the dark/light aesthetic.

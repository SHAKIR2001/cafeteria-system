/**
 * admin/script.js — Shared Admin JavaScript
 * Contains UI helpers, sidebar toggles, and chart drawing functions.
 * All hardcoded mock data and automatic DOM overwrites have been removed.
 */

document.addEventListener("DOMContentLoaded", function () {
    const menuIcon = document.getElementById("menuIcon");
    const sidebar = document.querySelector(".sidebar");

    if (menuIcon && sidebar) {
        menuIcon.addEventListener("click", function () {
            sidebar.classList.toggle("show-sidebar");
        });
    }
});

// Helper functions for status styling
function getOrderStatusClass(status) {
    if (status === "Processing") return "status-processing";
    if (status === "Ready") return "status-ready";
    if (status === "Completed") return "status-completed";
    if (status === "Cancelled") return "status-cancelled";
    return "";
}

function getPaymentStatusClass(status) {
    if (status === "Paid") return "payment-paid";
    if (status === "Failed") return "payment-failed";
    return "";
}

function getUserStatusClass(status) {
    if (status === "Active") return "user-active";
    if (status === "Pending") return "user-pending";
    if (status === "Suspended") return "user-suspended";
    return "";
}

// Draw Dashboard Sales/Revenue Line Chart
function drawDashboardSalesChart(orderData, revenueData) {
    const canvas = document.getElementById("salesChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    canvas.width = 350;
    canvas.height = 200;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    function drawLine(data, color) {
        ctx.strokeStyle = color;
        ctx.lineWidth = 3;
        ctx.beginPath();

        const maxVal = Math.max(...data, 1);
        data.forEach(function (value, index) {
            const x = 45 + index * 45;
            // Map value to coordinate
            const y = 165 - (value / maxVal) * 100;

            if (index === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });

        ctx.stroke();
    }

    ctx.strokeStyle = "#ddd";
    for (let i = 0; i < 4; i++) {
        const y = 165 - i * 40;
        ctx.beginPath();
        ctx.moveTo(35, y);
        ctx.lineTo(330, y);
        ctx.stroke();
    }

    drawLine(orderData, "#3d5afe");
    drawLine(revenueData, "#16a34a");
}

// Draw Reports Line Chart
function drawLineChart(canvas, labels, data, color) {
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    canvas.width = 400;
    canvas.height = 150;

    const max = Math.max(...data, 1);
    const left = 42;
    const top = 15;
    const width = 340;
    const height = 100;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = "#ddd";

    for (let i = 0; i <= 4; i++) {
        const y = top + i * 25;
        ctx.beginPath();
        ctx.moveTo(left, y);
        ctx.lineTo(390, y);
        ctx.stroke();
    }

    ctx.strokeStyle = color;
    ctx.lineWidth = 3;
    ctx.beginPath();

    data.forEach(function (value, index) {
        const x = left + (width / (data.length - 1)) * index;
        const y = top + height - (value / max) * height;

        if (index === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    });

    ctx.stroke();
}

// Draw Payment Methods Pie Chart
function drawPaymentPieChart(cash, card, other) {
    const canvas = document.getElementById("paymentMethodChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    canvas.width = 170;
    canvas.height = 130;

    const total = cash + card + other || 1;

    const values = [cash, card, other];
    const colors = ["#41af55", "#2563eb", "#f59e0b"];
    let start = -Math.PI / 2;

    values.forEach(function (value, index) {
        const angle = (value / total) * Math.PI * 2;

        ctx.beginPath();
        ctx.moveTo(70, 65);
        ctx.arc(70, 65, 50, start, start + angle);
        ctx.closePath();
        ctx.fillStyle = colors[index];
        ctx.fill();

        start += angle;
    });

    ctx.beginPath();
    ctx.arc(70, 65, 27, 0, Math.PI * 2);
    ctx.fillStyle = "white";
    ctx.fill();

    ctx.fillStyle = "black";
    ctx.font = "bold 16px Arial";
    ctx.fillText(total, 55, 63);
    ctx.font = "10px Arial";
    ctx.fillText("Total", 55, 80);

    const cashText = document.getElementById("cashText");
    const cardText = document.getElementById("cardText");
    const otherText = document.getElementById("otherText");

    if (cashText) cashText.textContent = cash + " (" + Math.round((cash / total) * 100) + "%)";
    if (cardText) cardText.textContent = card + " (" + Math.round((card / total) * 100) + "%)";
    if (otherText) otherText.textContent = other + " (" + Math.round((other / total) * 100) + "%)";
}

// CSV Export Utility
function exportCSV(fileName, data) {
    if (!data || data.length === 0) {
        alert("No data to export");
        return;
    }

    const headers = Object.keys(data[0]);
    let csv = headers.join(",") + "\n";

    data.forEach(function (row) {
        csv += headers.map(header => {
            let val = row[header] === null ? "" : String(row[header]);
            // Escape double quotes
            val = val.replace(/"/g, '""');
            return `"${val}"`;
        }).join(",") + "\n";
    });

    const file = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const fileUrl = URL.createObjectURL(file);

    const link = document.createElement("a");
    link.href = fileUrl;
    link.download = fileName;
    link.click();

    URL.revokeObjectURL(fileUrl);
}

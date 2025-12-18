import Toastify from "toastify-js";
import "toastify-js/src/toastify.css";

export function showToast(message, type = "success") {
  const background = {
    success: "linear-gradient(to right, #00b09b, #96c93d)",
    error: "linear-gradient(to right, #dc2626, #ef4444)",
    warning: "linear-gradient(to right, #d97706, #fbbf24)",
    info: "linear-gradient(to right, #2563eb, #3b82f6)",
  };

  Toastify({
    text: message,
    duration: 3500,
    close: true,
    gravity: "top",
    position: "right",
    stopOnFocus: true,
    style: { background: background[type] || background.success },
  }).showToast();
}

/**
 * Listener Global untuk Livewire Events
 */
export function registerToastListener() {
  // 🔥 2. Listener Livewire Events
  document.addEventListener("livewire:init", () => {
    Livewire.on("toast", (data) => {
      showToast(data.message, data.type);
    });
  });
}

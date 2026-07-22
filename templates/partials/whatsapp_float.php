<?php
// Key unificada: whatsapp_support_number
$waNumber = Database::getSetting('whatsapp_support_number', '');
if (empty($waNumber)) return;
$waUrl = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $waNumber);
?>
<a href="<?= htmlspecialchars($waUrl) ?>"
   class="wa-fab"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="Contactar con soporte por WhatsApp">
  <svg class="wa-fab__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
    <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c2.2 0 4.27.86 5.82 2.41a8.22 8.22 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23-1.48 0-2.93-.4-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.32a8.19 8.19 0 0 1-1.26-4.37c.01-4.54 3.7-8.23 8.25-8.23zm-2.9 4.36c-.18 0-.46.07-.7.34-.24.27-.91.89-.91 2.17s.93 2.52 1.06 2.69c.13.18 1.83 2.79 4.43 3.91.62.27 1.1.43 1.48.55.62.2 1.19.17 1.63.1.5-.07 1.53-.63 1.75-1.23.22-.6.22-1.12.15-1.23-.06-.1-.24-.17-.5-.3-.27-.13-1.54-.76-1.78-.85-.24-.09-.41-.13-.58.13-.17.27-.65.85-.8 1.02-.14.18-.29.2-.54.07-.25-.14-1.05-.39-2-1.23-.74-.66-1.24-1.47-1.39-1.72-.14-.25-.01-.38.11-.51.11-.11.25-.29.38-.43.12-.14.16-.24.24-.41.08-.17.04-.31-.02-.44-.06-.13-.57-1.38-.79-1.89-.2-.48-.41-.42-.57-.43l-.48-.01z"/>
  </svg>
  <span class="wa-fab__tooltip">Soporte WhatsApp</span>
</a>

<style>
.wa-fab {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  /* z-index alto para estar siempre por encima de cualquier layout */
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3.25rem;
  height: 3.25rem;
  border-radius: 50%;
  background: #25d366;
  color: #fff;
  box-shadow: 0 4px 14px rgba(37,211,102,.45), 0 1px 3px rgba(0,0,0,.2);
  text-decoration: none;
  transition: transform 180ms cubic-bezier(.16,1,.3,1),
              box-shadow 180ms cubic-bezier(.16,1,.3,1);
  animation: wa-bounce 2.4s ease-in-out 2s 3;
}
.wa-fab:hover,
.wa-fab:focus-visible {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(37,211,102,.55), 0 2px 6px rgba(0,0,0,.25);
  outline: none;
}
.wa-fab:active { transform: scale(.96); }

.wa-fab__icon {
  width: 1.75rem;
  height: 1.75rem;
  flex-shrink: 0;
}

/* Tooltip */
.wa-fab__tooltip {
  position: absolute;
  right: calc(100% + .6rem);
  top: 50%;
  transform: translateY(-50%) translateX(.5rem);
  background: rgba(0,0,0,.75);
  color: #fff;
  font-size: .75rem;
  font-family: inherit;
  white-space: nowrap;
  padding: .3rem .65rem;
  border-radius: .4rem;
  pointer-events: none;
  opacity: 0;
  transition: opacity 150ms ease, transform 150ms ease;
  backdrop-filter: blur(4px);
}
.wa-fab:hover .wa-fab__tooltip,
.wa-fab:focus-visible .wa-fab__tooltip {
  opacity: 1;
  transform: translateY(-50%) translateX(0);
}

/* Ligero rebote de entrada para llamar la atención */
@keyframes wa-bounce {
  0%, 100% { transform: translateY(0); }
  40%       { transform: translateY(-.55rem); }
  60%       { transform: translateY(-.25rem); }
}

/* En móvil sube un poco para no tapar la barra de navegación del SO */
@media (max-width: 640px) {
  .wa-fab {
    bottom: 1.25rem;
    right: 1rem;
    width: 3rem;
    height: 3rem;
  }
  .wa-fab__icon {
    width: 1.5rem;
    height: 1.5rem;
  }
  /* El tooltip en móvil no tiene hover, lo ocultamos para no molestar */
  .wa-fab__tooltip { display: none; }
}
</style>

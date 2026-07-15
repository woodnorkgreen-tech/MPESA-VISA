<template>

  <!-- ═══════════════════════════════════════════════════════════════════
       LANDING SPLASH
  ════════════════════════════════════════════════════════════════════ -->
  <div v-if="view === 'landing'" class="landing-root min-h-dvh flex flex-col select-none">

    <!-- ── Top logo bar ────────────────────────────────────────────────── -->
    <header class="relative z-10 flex items-center justify-between px-5 sm:px-8 lg:px-12 pt-safe pt-5 sm:pt-8 pb-2">

      <!-- LEFT: M-PESA logo -->
      <img src="/images/mpesa-logo.svg" alt="M-PESA"
        class="h-14 sm:h-20 md:h-24 object-contain object-left drop-shadow-lg" />

      <!-- RIGHT: VISA -->
      <img src="/images/visa-logo.svg" alt="Visa"
        class="h-6 sm:h-8 md:h-10 object-contain drop-shadow-lg" />

    </header>

    <!-- ── Hero area — vertically centred in the upper ~60% of the screen ── -->
    <!-- Bottom padding reserves the lower portion for the fans in the bg image -->
    <main class="relative z-10 flex-1 flex flex-col items-center lg:items-start justify-center px-6 sm:px-10 lg:px-16 text-center lg:text-left hero-content">

      <span class="italic font-black inline-flex items-center justify-center lg:justify-start gap-[.24em] mb-4 text-white drop-shadow"
        style="font-size: clamp(2.1rem, 5vw, 4.5rem)">
        GAME IKO<OnIcon />
      </span>

      <h1 class="text-white font-black leading-[1.12] mb-5 max-w-3xl tracking-[-0.03em]"
        style="font-size: clamp(1.7rem, 4vw, 4rem); text-shadow: 0 3px 24px rgba(0,0,0,0.5)">
        Welcome to <span class="text-safaricom-light">M-PESA GlobalPay Virtual Visa Card</span><br />
        <span class="italic uppercase">Semi-Final Viewing Event</span>
      </h1>

      <p class="max-w-xl text-white/72 text-sm sm:text-base lg:text-lg leading-relaxed mb-8">
        Join the live M-PESA GlobalPay and Visa fan experience. Predict the score, test your football knowledge and climb the leaderboard.
      </p>

      <button @click="view = 'register'"
        class="play-btn w-full max-w-xs py-4 rounded-xl font-extrabold text-base sm:text-lg transition active:scale-95">
        Join the game <span aria-hidden="true">→</span>
      </button>

      <button @click="goToPlay"
        class="mt-5 text-white/50 text-xs sm:text-sm hover:text-white transition underline-offset-2 hover:underline pb-safe">
        Already registered on this device? <span class="text-white font-semibold">Continue playing</span>
      </button>
    </main>

  </div>

  <!-- ═══════════════════════════════════════════════════════════════════
       REGISTRATION FORM
  ════════════════════════════════════════════════════════════════════ -->
  <div v-else-if="view === 'register'" class="event-surface min-h-dvh flex items-center justify-center p-4 sm:p-6 pt-safe pb-safe">
    <div class="w-full max-w-md sm:max-w-lg">

      <!-- Back to landing -->
      <button @click="view = 'landing'" class="flex items-center gap-1 text-gray-500 hover:text-gray-300 text-sm mb-5 transition">
        ← Back
      </button>

      <!-- Header -->
      <div class="text-center mb-6">
        <p class="brand-kicker mb-2">Player registration</p>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white mb-1">Join the game</h1>
        <p class="text-white/60 text-sm sm:text-base">Create your event profile in under a minute.</p>
      </div>

      <!-- Success state -->
      <div v-if="registered" class="bg-safaricom/10 border border-safaricom rounded-2xl p-8 text-center">
        <div class="text-5xl mb-4">🎉</div>
        <h2 class="text-2xl font-bold text-safaricom mb-2">You're in!</h2>
        <p class="text-gray-300 mb-1">Welcome, <strong>{{ nickname }}</strong></p>
        <p class="text-gray-400 text-sm mb-6">Watch the big screen for predictions and trivia rounds.</p>
        <button @click="goToPlay"
          class="w-full bg-safaricom hover:bg-safaricom-dark text-white font-bold py-4 rounded-xl transition text-base">
          Go to Game →
        </button>
      </div>

      <!-- Registration form -->
      <form v-else @submit.prevent="submit" class="glass-card rounded-2xl p-6 sm:p-8 space-y-4 sm:space-y-5">

        <div>
          <label class="block text-sm sm:text-base font-medium text-gray-300 mb-1.5">Nickname *</label>
          <input v-model="form.nickname" type="text" maxlength="50" minlength="2" required
            placeholder="What should we call you?"
            autocomplete="nickname" class="field-control px-4 py-3.5 text-base placeholder-white/30" />
          <p class="mt-1.5 text-xs text-gray-500">
            Your nickname is your identity for the whole event — no phone number or email needed.
            Pick something unique; it appears on the big screen.
          </p>
        </div>

        <label class="flex items-start gap-3 cursor-pointer">
          <input v-model="form.has_visa_card" type="checkbox"
            class="mt-0.5 w-5 h-5 rounded accent-visa flex-shrink-0" />
          <span class="text-sm sm:text-base text-gray-300 leading-snug">
            I have an <span class="text-visa-gold font-semibold">M-PESA GlobalPay Virtual Visa Card</span>
          </span>
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
          <input v-model="form.consent" type="checkbox" required
            class="mt-0.5 w-5 h-5 rounded accent-safaricom flex-shrink-0" />
          <span class="text-sm text-gray-400 leading-snug">
            I agree to take part in this event's games and accept the event rules *
          </span>
        </label>

        <p v-if="errorMsg" class="text-mpesa text-sm text-center">{{ errorMsg }}</p>

        <button type="submit" :disabled="submitting"
          class="w-full bg-safaricom hover:bg-safaricom-dark disabled:opacity-50 text-white font-bold py-4 rounded-xl transition text-base sm:text-lg">
          {{ submitting ? 'Creating your profile…' : 'Create profile →' }}
        </button>

        <p class="text-center text-xs text-gray-500 leading-snug">
          Keep this browser open during the event — your nickname and progress live on this device.
        </p>
      </form>

    </div>
  </div>

</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'
import OnIcon from '../brand/OnIcon.vue'

// ── State machine ─────────────────────────────────────────────────────────────
const view = ref('landing')   // 'landing' | 'register'

// ── Registration — nickname only, no personal data collected ─────────────────
const form = reactive({
  nickname:      '',
  has_visa_card: false,
  consent:       false,
})

const registered = ref(false)
const submitting  = ref(false)
const errorMsg    = ref('')
const nickname    = ref('')

async function submit() {
  submitting.value = true
  errorMsg.value   = ''
  try {
    const { data } = await axios.post('/api/players', form)
    sessionStorage.setItem('player_id', data.player_id)
    sessionStorage.setItem('player_nickname', data.nickname)
    sessionStorage.setItem('player_session_token', data.session_token)
    nickname.value   = data.nickname
    registered.value = true
  } catch (e) {
    errorMsg.value = e.response?.data?.message ?? 'Something went wrong. Try again.'
  } finally {
    submitting.value = false
  }
}

function goToPlay() {
  window.location.href = '/play'
}
</script>

<style scoped>
/* ── Landing: full-bleed background image ──────────────────────────────────
   Dark gradient overlay sits on top of the photo to keep text readable.
   Fallback colour (#001A0D) shows while the image loads or if it's missing.
─────────────────────────────────────────────────────────────────────────── */
.landing-root {
  background-color: #001A0D;
  background-image:
    linear-gradient(to bottom, rgba(0, 8, 4, .5), rgba(0, 10, 5, .12) 44%, rgba(0, 4, 2, .6)),
    url('/images/backgrounds/event-portrait.webp');

  /* Portrait phones (primary): fill the screen, anchor to the top so
     the stadium shows at the top and fans peek up from the bottom     */
  background-size: cover;
  background-position: center top;
  background-repeat: no-repeat;
}

/* ── Landscape phones (e.g. iPhone rotated) ────────────────────────────────
   Cover still applies but we shift position to centre the composition       */
@media (orientation: landscape) and (max-height: 500px) {
  .landing-root {
    background-position: center center;
  }
  /* Push content higher so it doesn't clash with fans area on short screens */
  .hero-content {
    padding-bottom: 0;
  }
}

/* ── Tablets portrait (768px+) — image is narrower than screen width ────────
   Switch to auto-height so the image fills height without over-cropping the
   fans; horizontal edges are filled by the background-color fallback.        */
@media (min-width: 768px) and (orientation: portrait) {
  .landing-root {
    background-size: cover;
    background-position: center top;
  }
}

/* ── Tablets landscape + desktop (768px+ wide, landscape) ──────────────────
   Image is portrait; cover will crop heavily. Use contain + gradient fill
   so the full image is visible centred on a green background.                */
@media (min-width: 768px) and (orientation: landscape) {
  .landing-root {
    background-image:
      linear-gradient(90deg, rgba(0, 12, 7, .84) 0%, rgba(0, 12, 7, .58) 48%, rgba(0, 8, 4, .08) 100%),
      url('/images/backgrounds/event-landscape.webp');
    background-size: cover;
    background-position: center;
  }
}

/* ── Hero area: reserve the lower ~35% of the screen for the fan photo ──── */
.hero-content {
  padding-bottom: clamp(7rem, 29vh, 15rem);
}
@media (min-width: 1024px) and (orientation: landscape) {
  .hero-content {
    padding-bottom: 4rem;
    padding-right: 43%;
  }
}

/* ── Play button — bright Safaricom green pill ──────────────────────────── */
.play-btn {
  background: linear-gradient(135deg, #00C65A 0%, #00A550 100%);
  box-shadow: 0 12px 32px rgba(0, 198, 90, 0.3), 0 2px 8px rgba(0,0,0,0.35);
  color: #002815;
}
.play-btn:hover {
  background: linear-gradient(135deg, #00D966 0%, #00B558 100%);
  box-shadow: 0 12px 40px rgba(0, 198, 90, 0.65), 0 2px 8px rgba(0,0,0,0.4);
}
.play-btn:active {
  transform: scale(0.96);
}
</style>

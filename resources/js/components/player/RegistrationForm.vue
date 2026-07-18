<template>

  <!-- ═══════════════════════════════════════════════════════════════════
       LANDING SPLASH
  ════════════════════════════════════════════════════════════════════ -->
  <div v-if="view === 'landing'" class="landing-root min-h-dvh flex flex-col select-none">

    <header class="landing-header relative z-20 flex items-center justify-between px-5 sm:px-8 lg:px-12 pt-safe pt-5 sm:pt-8 pb-2">

      <div class="flex items-center gap-3">
        <span class="landing-header-mark" aria-hidden="true"></span>
        <span class="text-xs font-black uppercase tracking-[.28em] text-white/78 sm:text-sm">FIFA World Cup 2026™</span>
      </div>

      <img src="/images/visa-fwc2026-lockup-white.png" alt="Visa FIFA World Cup 2026"
        class="h-10 max-w-[56vw] object-contain drop-shadow-lg sm:h-12 md:h-14 lg:hidden" />

      <img src="/images/visa-logo.svg" alt="Visa"
        class="hidden h-9 object-contain drop-shadow-lg lg:block xl:h-10" />

    </header>

    <div class="landing-partner-stage hidden lg:flex" aria-label="Visa Worldwide Partner FIFA World Cup 2026">
      <span class="landing-stage-line landing-stage-line-one" aria-hidden="true"></span>
      <span class="landing-stage-line landing-stage-line-two" aria-hidden="true"></span>
      <div class="landing-partner-mark">
        <img src="/images/visa-fwc2026-stacked-partner-white.png" alt="" class="h-full w-full object-contain" />
        <span class="landing-fifa-label" aria-hidden="true">FIFA</span>
      </div>
    </div>

    <!-- ── Hero area — vertically centred in the upper ~60% of the screen ── -->
    <!-- Bottom padding reserves the lower portion for the fans in the bg image -->
    <main class="relative z-10 flex-1 flex flex-col items-center lg:items-start justify-center px-6 sm:px-10 lg:px-16 text-center lg:text-left hero-content">

      <span class="landing-kicker">Visa presents</span>

      <h1 class="landing-title max-w-4xl">
        FIFA World Cup 2026™<br />
        <span>Watch Party</span>
      </h1>

      <p class="landing-command">Predict. Play. Win with Visa.</p>

      <p class="max-w-xl text-white/74 text-sm sm:text-base lg:text-lg leading-relaxed mb-7">
        Join the live finals watch party for Argentina vs Spain. Predict the score, test your Visa and football knowledge, and climb the leaderboard.
      </p>

      <button @click="view = 'register'"
        class="play-btn w-full max-w-xs py-4 font-extrabold text-base sm:text-lg transition active:scale-95">
        Join the game <span aria-hidden="true">→</span>
      </button>

      <button @click="view = 'login'"
        class="mt-5 text-white/50 text-xs sm:text-sm hover:text-white transition underline-offset-2 hover:underline pb-safe">
        Already registered? <span class="text-white font-semibold">Sign in</span>
      </button>
    </main>

    <aside class="landing-match-rail relative z-10 mx-5 mb-5 hidden items-center justify-between gap-6 border-t border-white/16 px-1 pt-4 text-white/70 sm:mx-8 lg:mx-16 lg:flex">
      <span>Argentina vs Spain</span>
      <span>Finals watch party</span>
      <span>Live predictions + trivia</span>
    </aside>

  </div>

  <!-- Returning players can restore their profile after closing the browser or changing device. -->
  <div v-else-if="view === 'login'" class="event-surface min-h-dvh flex items-center justify-center p-4 sm:p-6 pt-safe pb-safe">
    <div class="w-full max-w-md">
      <button @click="view = 'landing'; errorMsg = ''" class="mb-5 flex items-center gap-1 text-sm text-gray-500 transition hover:text-gray-300">← Back</button>
      <div class="mb-6 text-center">
        <p class="brand-kicker mb-2">Returning player</p>
        <h1 class="mb-1 text-2xl font-extrabold text-white sm:text-3xl">Welcome back</h1>
        <p class="text-sm text-white/60 sm:text-base">Use the nickname and game PIN you registered with.</p>
        <p class="mt-2 text-xs text-white/40">Older profile without a PIN? Enter a new 4-digit PIN once to secure it.</p>
      </div>

      <form @submit.prevent="login" class="glass-card space-y-5 rounded-2xl p-6 sm:p-8">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-gray-300">Nickname</label>
          <input v-model="loginForm.nickname" type="text" minlength="2" maxlength="50" required autocomplete="username"
            placeholder="Your event nickname" class="field-control px-4 py-3.5 text-base placeholder-white/30" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-gray-300">4-digit game PIN</label>
          <input v-model="loginForm.pin" type="password" inputmode="numeric" pattern="[0-9]{4}" minlength="4" maxlength="4" required autocomplete="current-password"
            placeholder="••••" class="field-control px-4 py-3.5 text-center text-xl tracking-[.5em] placeholder-white/30" />
        </div>
        <p v-if="errorMsg" class="text-center text-sm text-red-400">{{ errorMsg }}</p>
        <button type="submit" :disabled="submitting"
          class="w-full rounded-xl bg-visa py-4 text-base font-bold text-white transition hover:bg-visa/80 disabled:opacity-50">
          {{ submitting ? 'Signing in…' : 'Sign in →' }}
        </button>
        <button type="button" @click="view = 'register'; errorMsg = ''" class="w-full text-sm text-gray-400 hover:text-white">
          New player? Create a profile
        </button>
      </form>
    </div>
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
      <div v-if="registered" class="bg-visa/20 border border-visa-gold/40 rounded-2xl p-8 text-center">
        <div class="text-5xl mb-4">🎉</div>
        <h2 class="text-2xl font-bold text-visa-gold mb-2">You're in!</h2>
        <p class="text-gray-300 mb-1">Welcome, <strong>{{ nickname }}</strong></p>
        <p class="text-gray-400 text-sm mb-6">Watch the big screen for predictions and trivia rounds.</p>
        <button @click="goToPlay"
          class="w-full bg-visa hover:bg-visa/80 text-white font-bold py-4 rounded-xl transition text-base">
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

        <div>
          <label class="block text-sm sm:text-base font-medium text-gray-300 mb-1.5">Create a 4-digit game PIN *</label>
          <input v-model="form.pin" type="password" inputmode="numeric" pattern="[0-9]{4}" minlength="4" maxlength="4" required
            autocomplete="new-password" placeholder="••••"
            class="field-control px-4 py-3.5 text-center text-xl tracking-[.5em] placeholder-white/30" />
          <p class="mt-1.5 text-xs text-gray-500">Remember this PIN. It lets you return on this or another device.</p>
        </div>

        <label class="flex items-start gap-3 cursor-pointer">
          <input v-model="form.has_visa_card" type="checkbox"
            class="mt-0.5 w-5 h-5 rounded accent-visa flex-shrink-0" />
          <span class="text-sm sm:text-base text-gray-300 leading-snug">
            I have a <span class="text-visa-gold font-semibold">Visa card</span>
          </span>
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
          <input v-model="form.consent" type="checkbox" required
            class="mt-0.5 w-5 h-5 rounded accent-visa flex-shrink-0" />
          <span class="text-sm text-gray-400 leading-snug">
            I agree to take part in this event's games and accept the event rules *
          </span>
        </label>

        <p v-if="errorMsg" class="text-red-400 text-sm text-center">{{ errorMsg }}</p>

        <button type="submit" :disabled="submitting"
          class="w-full bg-visa hover:bg-visa/80 disabled:opacity-50 text-white font-bold py-4 rounded-xl transition text-base sm:text-lg">
          {{ submitting ? 'Creating your profile…' : 'Create profile →' }}
        </button>

        <p class="text-center text-xs text-gray-500 leading-snug">
          Your progress is saved. Use your nickname and PIN whenever you need to sign back in.
        </p>
      </form>

    </div>
  </div>

</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'

// ── State machine ─────────────────────────────────────────────────────────────
const view = ref('landing')   // 'landing' | 'register' | 'login'

// ── Registration — nickname only, no personal data collected ─────────────────
const form = reactive({
  nickname:      '',
  pin:           '',
  has_visa_card: false,
  consent:       false,
})
const loginForm = reactive({ nickname: '', pin: '' })

const registered = ref(false)
const submitting  = ref(false)
const errorMsg    = ref('')
const nickname    = ref('')

async function submit() {
  submitting.value = true
  errorMsg.value   = ''
  try {
    const { data } = await axios.post('/api/players', form)
    persistPlayer(data)
    nickname.value   = data.nickname
    registered.value = true
  } catch (e) {
    errorMsg.value = e.response?.data?.message ?? 'Something went wrong. Try again.'
  } finally {
    submitting.value = false
  }
}

function persistPlayer(data) {
  localStorage.setItem('player_id', data.player_id)
  localStorage.setItem('player_nickname', data.nickname)
  localStorage.setItem('player_session_token', data.session_token)
  // Keep the current tab compatible with older code while localStorage provides persistence.
  sessionStorage.setItem('player_id', data.player_id)
  sessionStorage.setItem('player_nickname', data.nickname)
  sessionStorage.setItem('player_session_token', data.session_token)
}

async function login() {
  submitting.value = true
  errorMsg.value = ''
  try {
    const { data } = await axios.post('/api/players/login', loginForm)
    persistPlayer(data)
    window.location.href = '/play'
  } catch (e) {
    errorMsg.value = e.response?.data?.message ?? 'Unable to sign in. Try again.'
  } finally {
    submitting.value = false
  }
}

function goToPlay() {
  if (localStorage.getItem('player_session_token') || sessionStorage.getItem('player_session_token')) {
    window.location.href = '/play'
  } else {
    errorMsg.value = 'Sign in to continue.'
    view.value = 'login'
  }
}
</script>

<style scoped>
/* ── Landing: Visa-blue event background ───────────────────────────────────
   Uses Visa Blue (#1434CB) with subtle graphic bands.
─────────────────────────────────────────────────────────────────────────── */
.landing-root {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  background-color: #1434CB;
  background-image:
    linear-gradient(180deg, rgba(5, 15, 70, .18), rgba(5, 15, 70, .48)),
    linear-gradient(118deg, transparent 0 58%, rgba(255, 255, 255, .11) 58% 68%, transparent 68%),
    linear-gradient(120deg, rgba(255, 255, 255, .075) 0 1px, transparent 1px 96px),
    linear-gradient(115deg, #1434CB 0%, #1939D2 45%, #0A1F8F 100%);
  background-size: cover, cover, 96px 96px, cover;
  background-position: center;
}

.landing-root::before,
.landing-root::after {
  content: '';
  position: fixed;
  inset: 0;
  z-index: -1;
  pointer-events: none;
}

.landing-root::before {
  background:
    linear-gradient(104deg, transparent 0 49%, rgba(255, 255, 255, .15) 49.2% 51%, transparent 51.2%),
    linear-gradient(153deg, transparent 0 60%, rgba(255, 255, 255, .08) 60.1% 61.5%, transparent 61.7%),
    linear-gradient(18deg, transparent 0 72%, rgba(247, 182, 0, .34) 72.2% 72.55%, transparent 72.8%);
}

.landing-root::after {
  background:
    linear-gradient(90deg, rgba(6, 22, 95, .5) 0%, transparent 42%),
    linear-gradient(0deg, rgba(3, 12, 57, .46) 0%, transparent 36%);
}

.landing-root .landing-header::before {
  content: '';
  position: absolute;
  left: clamp(1.25rem, 3vw, 3rem);
  right: clamp(1.25rem, 3vw, 3rem);
  top: max(env(safe-area-inset-top), 0px);
  height: 2px;
  background: linear-gradient(90deg, #f7b600, rgba(247, 182, 0, 0));
}

.landing-header-mark {
  width: .18rem;
  height: 1.1rem;
  border-radius: 1px;
  background: #f7b600;
  box-shadow: 0 0 18px rgba(247, 182, 0, .35);
}

.landing-kicker {
  margin-bottom: 1rem;
  color: #f7b600;
  font-size: clamp(.78rem, 1.1vw, 1.1rem);
  font-weight: 850;
  letter-spacing: .34em;
  text-transform: uppercase;
}

.landing-title {
  margin-bottom: 1rem;
  color: #fff;
  font-size: clamp(2.4rem, 5.9vw, 6.7rem);
  font-weight: 950;
  line-height: .92;
  letter-spacing: 0;
  text-shadow: 0 22px 60px rgba(0, 0, 0, .34);
}

.landing-title span {
  color: #fff;
  font-weight: 650;
}

.landing-command {
  margin-bottom: 1.15rem;
  color: #f7b600;
  font-size: clamp(1.35rem, 2.75vw, 3.55rem);
  font-style: italic;
  font-weight: 950;
  line-height: 1.05;
  text-transform: uppercase;
  text-shadow: 0 14px 36px rgba(0, 0, 0, .28);
}

.landing-partner-stage {
  position: absolute;
  inset: 0;
  z-index: 8;
  pointer-events: none;
}

.landing-partner-stage::before {
  content: '';
  position: absolute;
  right: clamp(8rem, 18vw, 19rem);
  top: clamp(9rem, 20vh, 13rem);
  width: clamp(23rem, 34vw, 42rem);
  height: clamp(23rem, 34vw, 42rem);
  border-top: 1px solid rgba(255, 255, 255, .14);
  border-bottom: 1px solid rgba(255, 255, 255, .08);
  transform: rotate(-14deg) skewX(-8deg);
}

.landing-stage-line {
  position: absolute;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .34), transparent);
}

.landing-stage-line-one {
  right: clamp(5rem, 11vw, 12rem);
  top: 30%;
  width: clamp(20rem, 36vw, 45rem);
  transform: rotate(-16deg);
}

.landing-stage-line-two {
  right: clamp(4rem, 9vw, 10rem);
  top: 70%;
  width: clamp(17rem, 30vw, 38rem);
  transform: rotate(14deg);
}

.landing-partner-mark {
  position: absolute;
  top: clamp(9.5rem, 23vh, 14rem);
  right: clamp(8rem, 16vw, 18rem);
  width: clamp(15rem, 21vw, 24rem);
  aspect-ratio: 900 / 1180;
  align-items: center;
  justify-content: center;
  background: transparent;
  filter: drop-shadow(0 28px 48px rgba(0, 0, 0, .24));
}

.landing-fifa-label {
  position: absolute;
  left: 50%;
  top: 77.6%;
  transform: translate(-50%, -50%);
  color: #050505;
  font-size: clamp(1.1rem, 1.85vw, 2rem);
  font-weight: 900;
  letter-spacing: .02em;
  line-height: 1;
}

.landing-match-rail {
  font-size: clamp(.68rem, .85vw, .9rem);
  font-weight: 800;
  letter-spacing: .22em;
  text-transform: uppercase;
}

/* ── Landscape phones (e.g. iPhone rotated) ──────────────────────────────── */
@media (orientation: landscape) and (max-height: 500px) {
  .landing-root .landing-header {
    padding-top: .75rem;
  }
  .hero-content {
    justify-content: flex-start;
    padding-top: clamp(.35rem, 3vh, 1rem);
    padding-bottom: 0;
  }
  .landing-kicker {
    margin-bottom: .45rem;
    font-size: .66rem;
  }
  .landing-title {
    margin-bottom: .45rem;
    font-size: clamp(1.75rem, 7vw, 2.6rem);
  }
  .landing-command {
    margin-bottom: .55rem;
    font-size: clamp(1rem, 4.8vw, 1.55rem);
  }
}

@media (min-width: 768px) and (orientation: portrait) {
  .landing-root {
    background-size: 96px 96px, cover;
  }
}

@media (min-width: 768px) and (orientation: landscape) {
  .landing-root {
    background-image:
      linear-gradient(180deg, rgba(5, 15, 70, .14), rgba(5, 15, 70, .45)),
      linear-gradient(118deg, transparent 0 58%, rgba(255, 255, 255, .1) 58% 68%, transparent 68%),
      linear-gradient(120deg, rgba(255, 255, 255, .07) 0 1px, transparent 1px 118px),
      linear-gradient(108deg, #1434CB 0%, #183AD0 48%, #0A1F8F 100%);
    background-size: cover, cover, 118px 118px, cover;
  }
}

.hero-content {
  padding-bottom: clamp(7rem, 29vh, 15rem);
}
@media (min-width: 1024px) and (orientation: landscape) {
  .hero-content {
    padding-bottom: 3rem;
    padding-right: 45%;
  }
}

@media (max-width: 640px) {
  .landing-root {
    background-image:
      linear-gradient(180deg, rgba(5, 15, 70, .1), rgba(5, 15, 70, .5)),
      linear-gradient(118deg, transparent 0 55%, rgba(255, 255, 255, .1) 55% 66%, transparent 66%),
      linear-gradient(120deg, rgba(255, 255, 255, .06) 0 1px, transparent 1px 78px),
      linear-gradient(115deg, #1434CB 0%, #1939D2 46%, #0A1F8F 100%);
    background-size: cover, cover, 78px 78px, cover;
  }
  .landing-root::before {
    background:
      linear-gradient(106deg, transparent 0 42%, rgba(255, 255, 255, .12) 42.2% 44%, transparent 44.2%),
      linear-gradient(18deg, transparent 0 73%, rgba(247, 182, 0, .24) 73.15% 73.45%, transparent 73.7%);
  }
  .landing-root .landing-header {
    padding-left: 1.1rem;
    padding-right: 1.1rem;
  }
  .landing-header-mark {
    height: .95rem;
  }
  .hero-content {
    justify-content: center;
    padding-left: 1.25rem;
    padding-right: 1.25rem;
    padding-bottom: clamp(2rem, 8vh, 4rem);
  }
  .landing-kicker {
    margin-bottom: .75rem;
    font-size: .68rem;
    letter-spacing: .28em;
  }
  .landing-title {
    margin-bottom: .7rem;
    font-size: clamp(2.2rem, 13vw, 3.45rem);
    line-height: .96;
  }
  .landing-command {
    margin-bottom: .9rem;
    font-size: clamp(1.2rem, 8vw, 2rem);
    line-height: 1.08;
  }
  .play-btn {
    max-width: 18rem;
    padding-top: .9rem;
    padding-bottom: .9rem;
  }
}

/* ── Play button — bright Visa call-to-action ──────────────────────────── */
.play-btn {
  border-radius: .5rem;
  background: linear-gradient(135deg, #f7b600 0%, #ffcf40 100%);
  box-shadow: 0 16px 38px rgba(247, 182, 0, 0.34), 0 2px 8px rgba(0,0,0,0.35);
  color: #070b2a;
}
.play-btn:hover {
  background: linear-gradient(135deg, #ffcf40 0%, #f7b600 100%);
  box-shadow: 0 12px 40px rgba(247, 182, 0, 0.5), 0 2px 8px rgba(0,0,0,0.4);
}
.play-btn:active {
  transform: scale(0.96);
}
</style>

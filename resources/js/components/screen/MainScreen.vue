<template>
  <!-- screen-root fixes the view to the viewport and prevents any scrolling on TV browsers -->
  <div class="screen-root screen-brand-bg text-white font-display select-none flex flex-col">

    <div class="fixed right-3 top-3 z-50 flex items-center gap-2 print:hidden">
      <span v-if="linkMessage" class="rounded-full bg-black/70 px-3 py-2 text-xs font-bold text-white backdrop-blur">{{ linkMessage }}</span>
      <button type="button" @click="copyScreenLink" title="Copy public Main Screen link"
        class="rounded-full border border-white/15 bg-black/55 px-3 py-2 text-xs font-bold text-white backdrop-blur hover:bg-black/75 focus:outline-none focus:ring-2 focus:ring-safaricom-light">
        🔗 <span class="hidden sm:inline">Public link</span>
      </button>
      <button type="button" @click="toggleFullscreen" :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
        class="rounded-full border border-white/15 bg-black/55 px-3 py-2 text-xs font-bold text-white backdrop-blur hover:bg-black/75 focus:outline-none focus:ring-2 focus:ring-safaricom-light">
        {{ isFullscreen ? '✕' : '⛶' }} <span class="hidden sm:inline">{{ isFullscreen ? 'Exit' : 'Fullscreen' }}</span>
      </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         LOBBY / PREDICTIONS
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-if="['lobby', 'predictions_open', 'predictions_closed'].includes(phase)">
      <div class="flex-1 flex flex-col items-center justify-center px-8 lg:px-16 text-center">

        <!-- Title — clamp scales from small monitors to 4K -->
        <h1 class="font-black italic text-white tracking-tight leading-none mb-3 flex items-center justify-center gap-[.24em]"
          style="font-size: clamp(3rem, 7vw, 9rem)">
          <span>GAME IKO</span><OnIcon />
        </h1>
        <div class="flex items-center justify-center gap-5 lg:gap-8 mb-8 lg:mb-12">
          <span class="text-gray-300" style="font-size: clamp(0.9rem, 1.6vw, 2rem)">Powered by</span>
          <img src="/images/mpesa-logo.svg" alt="M-PESA GlobalPay"
            class="object-contain drop-shadow-lg" style="height: clamp(2.2rem, 4.5vw, 5.5rem)" />
          <span class="w-px bg-white/30 self-stretch"></span>
          <img src="/images/visa-logo.svg" alt="Visa"
            class="object-contain drop-shadow-lg" style="height: clamp(1.4rem, 3vw, 3.6rem)" />
        </div>

        <!-- Broadcast layout: join left, event status centre, recent activity right -->
        <div class="broadcast-grid mb-6 grid w-full grid-cols-[minmax(12rem,.85fr)_minmax(18rem,1.25fr)_minmax(15rem,1fr)] items-center gap-6 lg:gap-10">
          <!-- LEFT: QR — large enough to scan from across the room -->
          <div class="flex flex-col items-center flex-shrink-0">
            <div class="bg-white p-4 lg:p-5 rounded-3xl shadow-2xl">
              <canvas ref="qrCanvas" :width="qrSize" :height="qrSize"></canvas>
              <p class="text-gray-700 font-bold text-center mt-2 tabular-nums"
                style="font-size: clamp(0.7rem, 1.1vw, 1.2rem)">
                {{ joinUrlDisplay }}
              </p>
            </div>
            <p class="font-black text-white leading-tight mt-4"
              style="font-size: clamp(1.3rem, 2.4vw, 3.2rem)">
              Scan to play
            </p>
            <p class="text-gray-400 mt-1" style="font-size: clamp(0.8rem, 1.2vw, 1.6rem)">
              Register &amp; make your predictions
            </p>
          </div>

          <!-- CENTRE: kick-off and prediction state remain on the screen axis -->
          <div class="flex-1 flex flex-col items-center text-center">
            <div class="rounded-2xl border px-6 py-3"
              :class="phase === 'predictions_closed' ? 'border-orange-400/30 bg-orange-400/10' : 'border-safaricom-light/30 bg-safaricom/10'">
              <p class="font-black uppercase tracking-widest"
                :class="phase === 'predictions_closed' ? 'text-orange-400' : 'text-safaricom-light'"
                style="font-size: clamp(.65rem, 1vw, 1rem)">
                {{ phase === 'predictions_closed' ? '🔒 Predictions closed' : '● Predictions open' }}
              </p>
              <p class="mt-1 font-black tabular-nums text-white" style="font-size: clamp(1.1rem, 2.1vw, 2.7rem)">
                {{ predictionCount }} locked in
              </p>
            </div>
            <div v-if="phase === 'predictions_open'" class="mt-4 max-w-xl rounded-xl border border-visa-gold/25 bg-visa-gold/10 px-4 py-3">
              <p class="font-black uppercase tracking-widest text-visa-gold" style="font-size: clamp(.6rem,.9vw,.9rem)">Prediction rule</p>
              <p class="mt-1 font-semibold leading-snug text-white" style="font-size: clamp(.8rem,1.25vw,1.35rem)">
                Final score means the score after 90 minutes + stoppage time.
              </p>
              <p class="mt-1 text-gray-400" style="font-size: clamp(.65rem,.95vw,1rem)">Extra time and penalty shootouts do not count.</p>
            </div>
            <div v-if="match.kickoff_at && kickoffCountdown" class="mt-4 w-full max-w-lg rounded-2xl border border-white/10 bg-black/30 px-6 py-4">
              <p class="text-gray-500 uppercase tracking-widest" style="font-size: clamp(.6rem, 1vw, 1rem)">Kick-off countdown</p>
              <p class="font-black tabular-nums text-white" style="font-size: clamp(1.35rem, 2.8vw, 3.6rem)">{{ kickoffCountdown }}</p>
              <p class="text-gray-500" style="font-size: clamp(.6rem, .9vw, .9rem)">{{ match.venue }}</p>
            </div>
          </div>

          <!-- RIGHT: latest locked-in entries, newest first -->
          <aside class="broadcast-latest min-w-0 rounded-2xl border border-white/10 bg-black/30 p-4 text-left lg:p-5">
            <div class="mb-3 flex items-center justify-between gap-3 border-b border-white/10 pb-3">
              <div>
                <p class="font-black uppercase tracking-widest text-white" style="font-size: clamp(.65rem,1vw,1rem)">Latest locked in</p>
                <p class="mt-0.5 text-gray-500" style="font-size: clamp(.55rem,.8vw,.8rem)">{{ predictionFeed.length }} players · newest first</p>
              </div>
              <span class="h-2.5 w-2.5 shrink-0 animate-pulse rounded-full bg-safaricom-light"></span>
            </div>
            <TransitionGroup v-if="predictionFeed.length" name="ticker" tag="ol" class="prediction-feed-scroll max-h-[42vh] space-y-2 overflow-y-auto overscroll-contain pr-1">
              <li v-for="(entry, idx) in predictionFeed" :key="entry.id"
                class="flex min-w-0 items-center gap-3 rounded-xl border border-white/5 bg-white/5 px-3 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-safaricom/20 text-xs font-black text-safaricom-light">{{ idx + 1 }}</span>
                <div class="min-w-0">
                  <p class="truncate font-bold text-white" style="font-size: clamp(.75rem,1.2vw,1.3rem)">{{ entry.nickname }}</p>
                  <p class="text-gray-500" style="font-size: clamp(.55rem,.75vw,.75rem)">Prediction locked ✓</p>
                </div>
              </li>
            </TransitionGroup>
            <p v-else class="py-8 text-center text-sm text-gray-600">Waiting for the first prediction…</p>
          </aside>
        </div>

        <p class="mt-6 text-gray-700" style="font-size: clamp(0.75rem, 1.2vw, 1.5rem)">
          {{ playerCount }} players registered
        </p>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         TRIVIA LIVE
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else-if="phase === 'trivia_live' && question">
      <div class="phase-enter flex-1 flex flex-col px-8 lg:px-16 pt-6 lg:pt-10 pb-4">

        <!-- Top bar: double-points badge + huge countdown -->
        <div class="flex items-center justify-between mb-6 lg:mb-8 flex-shrink-0">
          <div class="flex items-center gap-4">
          <span class="rounded-full border border-white/10 bg-black/20 px-4 py-2 font-bold uppercase tracking-widest text-gray-300"
            style="font-size: clamp(0.65rem, 1.1vw, 1.1rem)">
            Round {{ round.current }} / {{ round.total }}
          </span>
          <span v-if="question.is_double_points"
            class="bg-visa-gold text-black font-black uppercase tracking-widest animate-pulse rounded-full px-5 py-2"
            style="font-size: clamp(0.75rem, 1.5vw, 1.5rem)">
            ⚡ DOUBLE POINTS ⚡
          </span>
          <span v-else class="text-gray-700" style="font-size: clamp(0.75rem, 1.2vw, 1.2rem)">
            Question {{ question.order_index }}
          </span>
          </div>

          <!-- Countdown ring — viewport-sized so it's always visible from the back row -->
          <div class="flex items-center gap-4 lg:gap-7">
            <div class="text-right">
              <Transition name="count" mode="out-in">
                <p :key="question.answer_count" class="font-black text-safaricom-light tabular-nums" style="font-size: clamp(1.4rem, 3vw, 3.5rem)">{{ question.answer_count ?? 0 }}</p>
              </Transition>
              <p class="text-gray-500 uppercase tracking-wider" style="font-size: clamp(.55rem, .9vw, .9rem)">Answers live</p>
            </div>
          <div class="relative flex-shrink-0" :style="{ width: timerSize, height: timerSize }">
            <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
              <circle cx="60" cy="60" r="52" fill="none" stroke="#1f2937" stroke-width="8" />
              <circle cx="60" cy="60" r="52" fill="none"
                :stroke="timerColor"
                stroke-width="8"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="dashOffset"
                style="transition: stroke-dashoffset 1s linear;" />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center font-black"
              :class="timeLeft <= 5 ? 'text-mpesa animate-pulse' : 'text-white'"
              style="font-size: clamp(1.5rem, 4vw, 5rem)">
              {{ timeLeft }}
            </span>
          </div>
          </div>
        </div>

        <!-- Question text — the most important thing on the screen -->
        <p class="font-bold text-center leading-tight flex-1 flex items-center justify-center"
          style="font-size: clamp(1.5rem, 3.5vw, 5rem)">
          {{ question.text }}
        </p>

        <!-- Answer options grid -->
        <div class="grid gap-4 lg:gap-5 mt-6 flex-shrink-0"
          :class="question.options.length === 2 ? 'grid-cols-2' : 'grid-cols-2'">
          <div v-for="(opt, idx) in question.options" :key="idx"
            class="bg-white/10 border border-white/20 rounded-2xl flex items-center gap-4 px-5 lg:px-8 py-4 lg:py-6">
            <span class="rounded-full bg-white/20 font-black flex items-center justify-center flex-shrink-0"
              style="font-size: clamp(0.875rem, 1.8vw, 2rem);
                     width: clamp(2rem, 4vw, 4.5rem);
                     height: clamp(2rem, 4vw, 4.5rem)">
              {{ optLabels[idx] }}
            </span>
            <span class="font-semibold leading-snug" style="font-size: clamp(0.875rem, 2vw, 2.5rem)">
              {{ opt }}
            </span>
          </div>
        </div>

        <LiveLeaderboardStrip v-if="leaderboard.length" :entries="leaderboard" :show-phone="showPhone" class="mt-4 flex-shrink-0" />

      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         TRIVIA REVEAL
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else-if="phase === 'trivia_reveal' && question">
      <div class="flex-1 flex flex-col px-8 lg:px-16 pt-8 pb-4">
        <p class="text-center text-gray-400 mb-4 flex-shrink-0"
          style="font-size: clamp(0.875rem, 1.5vw, 1.8rem)">
          Answer Revealed
        </p>
        <p class="text-center font-bold mb-6 flex-shrink-0"
          style="font-size: clamp(1.25rem, 2.5vw, 3.5rem)">
          {{ question.text }}
        </p>

        <div class="grid gap-4 mb-8 flex-shrink-0"
          :class="question.options.length === 2 ? 'grid-cols-2' : 'grid-cols-2'">
          <div v-for="(opt, idx) in question.options" :key="idx"
            :class="opt === question.correct_answer
              ? 'bg-safaricom-light/30 border-safaricom-light text-white'
              : 'bg-white/5 border-white/10 text-gray-500'"
            class="border-2 rounded-2xl flex items-center gap-4 px-5 lg:px-8 py-4 lg:py-5 transition-all">
            <span class="text-2xl lg:text-3xl flex-shrink-0">
              {{ opt === question.correct_answer ? '✅' : '' }}
            </span>
            <span class="font-semibold leading-snug"
              :class="opt === question.correct_answer ? 'font-black text-white' : ''"
              style="font-size: clamp(0.875rem, 2vw, 2.5rem)">
              {{ opt }}
            </span>
            <div class="ml-auto shrink-0 text-right">
              <p class="font-black tabular-nums" :class="opt === question.correct_answer ? 'text-safaricom-light' : 'text-gray-500'"
                style="font-size: clamp(.8rem, 1.5vw, 1.7rem)">{{ optionCount(opt) }}</p>
              <p class="text-gray-600" style="font-size: clamp(.55rem, .8vw, .8rem)">{{ optionPercent(opt) }}%</p>
            </div>
          </div>
        </div>

        <div class="flex-1 overflow-hidden">
          <Leaderboard :entries="leaderboard" :show-phone="showPhone" />
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         TRIVIA COMPLETE
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else-if="phase === 'trivia_complete'">
      <div class="flex-1 flex flex-col items-center justify-center px-8 lg:px-16 text-center">
        <img src="/images/brand/football-celebration.webp" alt="Football trivia"
          class="object-cover rounded-3xl shadow-2xl mb-5" style="width: clamp(8rem, 13vw, 16rem); aspect-ratio: 4 / 5" />
        <h2 class="font-black text-visa-gold mb-6"
          style="font-size: clamp(2rem, 5vw, 7rem)">
          TRIVIA CHAMPION
        </h2>
        <div v-if="leaderboard[0]" class="bg-white/10 rounded-3xl px-12 lg:px-20 py-8 lg:py-12 mb-8">
          <p class="font-black text-white mb-2" style="font-size: clamp(2rem, 5.5vw, 8rem)">
            {{ leaderboard[0].nickname }}
          </p>
          <p class="text-visa-gold font-bold" style="font-size: clamp(1.25rem, 3vw, 4rem)">
            {{ leaderboard[0].trivia_score?.toLocaleString() }} pts
          </p>
        </div>
        <div class="w-full max-w-3xl">
          <Leaderboard :entries="leaderboard" :show-phone="showPhone" />
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         MATCH ENDED / PREDICTION REVEAL
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else-if="['match_ended', 'prediction_reveal'].includes(phase)">
      <div class="flex-1 flex flex-col items-center justify-center px-8 text-center overflow-hidden">
        <img src="/images/brand/world-cup-trophy.png" alt="World Cup trophy"
          class="object-contain drop-shadow-2xl mb-5" style="width: clamp(6rem, 10vw, 12rem); max-height: 24vh" />
        <h2 class="font-black text-visa-gold mb-8"
          style="font-size: clamp(2rem, 5vw, 7rem)">
          PREDICTION CHAMPION
        </h2>
        <div v-if="leaderboard[0]" class="bg-white/10 rounded-3xl px-12 lg:px-20 py-5 lg:py-7 mb-5">
          <p class="font-black text-white mb-2" style="font-size: clamp(2rem, 5.5vw, 8rem)">
            {{ leaderboard[0].nickname }}
          </p>
          <p class="text-mpesa font-bold" style="font-size: clamp(1.25rem, 3vw, 4rem)">
            {{ leaderboard[0].prediction_score?.toLocaleString() ?? '—' }} pts
          </p>
        </div>
        <div class="w-full max-w-3xl overflow-hidden">
          <Leaderboard :entries="leaderboard" :show-phone="showPhone" title="Prediction leaderboard" />
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         FALLBACK
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else>
      <div class="flex-1 flex items-center justify-center">
        <p class="text-white font-black italic flex items-center gap-[.24em]" style="font-size: clamp(1.5rem, 4vw, 5rem)">
          <span>GAME IKO</span><OnIcon />
        </p>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         BOTTOM BRAND STRIP
    ══════════════════════════════════════════════════════════════════════ -->
    <div class="flex-shrink-0 bg-gradient-to-r from-safaricom-light/80 via-mpesa/80 to-visa/80
                flex items-center justify-center gap-6 lg:gap-12 px-8"
      style="height: clamp(2.5rem, 4vh, 5rem)">
      <img src="/images/mpesa-logo.svg" alt="M-PESA GlobalPay"
        class="object-contain opacity-90" style="height: clamp(1.4rem, 2.6vh, 3rem)" />
      <span class="text-white/30">·</span>
      <img src="/images/visa-logo.svg" alt="Visa"
        class="object-contain opacity-90" style="height: clamp(0.9rem, 1.8vh, 2rem)" />
      <span class="text-white/30">·</span>
      <span class="text-white font-semibold tracking-widest opacity-80"
        style="font-size: clamp(0.6rem, 1.2vw, 1.2rem)">SAFARICOM</span>
    </div>

    <!-- Connection error -->
    <div v-if="error"
      class="fixed bottom-20 right-4 bg-mpesa text-white text-xs px-3 py-1.5 rounded-full opacity-80">
      {{ error }}
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import QRCode from 'qrcode'
import axios from 'axios'
import { useEventState } from '../../composables/useEventState'
import Leaderboard from './Leaderboard.vue'
import OnIcon from '../brand/OnIcon.vue'
import LiveLeaderboardStrip from './LiveLeaderboardStrip.vue'

const { phase, question, leaderboard, playerCount, predictionCount, showPhone, recentPredictions, match, round, error } = useEventState(1500)

// ── QR Code — size scales with viewport, capped for readability ───────────────
const qrCanvas = ref(null)
// Guests join via the same host that serves this screen. APP_URL (the meta tag)
// may be a dev hostname that venue phones cannot resolve — never encode it in the QR.
const appUrl   = window.location.origin
const screenUrl = `${window.location.origin}/screen`
// Shown under the QR so guests can type the address if scanning fails
const joinUrlDisplay = appUrl.replace(/^https?:\/\//, '')
const isFullscreen = ref(!!document.fullscreenElement)
const linkMessage = ref('')
const predictionFeed = ref([])
let linkMessageTimer = null
let predictionFeedTimer = null

async function loadPredictionFeed() {
  try {
    const { data } = await axios.get('/api/predictions/feed')
    predictionFeed.value = data.entries ?? []
  } catch {
    // Shared event polling continues; retain the last successful feed on transient errors.
  }
}

async function copyScreenLink() {
  try {
    await navigator.clipboard.writeText(screenUrl)
    linkMessage.value = 'Screen link copied'
  } catch {
    window.prompt('Copy the public Main Screen link:', screenUrl)
    linkMessage.value = 'Public link ready'
  }
  clearTimeout(linkMessageTimer)
  linkMessageTimer = setTimeout(() => { linkMessage.value = '' }, 2500)
}

async function toggleFullscreen() {
  try {
    if (document.fullscreenElement) await document.exitFullscreen()
    else await document.documentElement.requestFullscreen()
  } catch {
    linkMessage.value = 'Fullscreen is blocked by this browser'
  }
}

function syncFullscreen() { isFullscreen.value = !!document.fullscreenElement }

// QR canvas: ~20% of the smaller viewport dimension, min 160, max 320
const qrSize = computed(() =>
  Math.min(Math.max(Math.round(Math.min(window.innerWidth, window.innerHeight) * 0.2), 160), 320)
)

onMounted(() => {
  document.addEventListener('fullscreenchange', syncFullscreen)
  loadPredictionFeed()
  predictionFeedTimer = setInterval(loadPredictionFeed, 3000)
  if (qrCanvas.value) {
    QRCode.toCanvas(qrCanvas.value, appUrl, {
      width:  qrSize.value,
      margin: 1,
      color: { dark: '#000000', light: '#ffffff' },
    })
  }
})

// ── Trivia timer ──────────────────────────────────────────────────────────────
const optLabels     = ['A', 'B', 'C', 'D']
const circumference = 2 * Math.PI * 52   // matches r="52" in the SVG

// Countdown ring: 10vw wide, capped at 160px so it doesn't eat the layout
const timerSize = `clamp(5rem, 10vw, 10rem)`

const timeLeft   = ref(0)
const nowTick = ref(Date.now())
const kickoffCountdown = computed(() => {
  if (!match.value?.kickoff_at) return ''
  const remaining = Math.max(0, new Date(match.value.kickoff_at).getTime() - nowTick.value)
  if (remaining <= 0) return 'KICK-OFF TIME'
  const seconds = Math.floor(remaining / 1000)
  const days = Math.floor(seconds / 86400)
  const hours = Math.floor((seconds % 86400) / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  return [days ? `${days}d` : null, `${String(hours).padStart(2, '0')}h`, `${String(minutes).padStart(2, '0')}m`, `${String(secs).padStart(2, '0')}s`].filter(Boolean).join(' ')
})
const totalQuestionAnswers = computed(() => Object.values(question.value?.answer_distribution ?? {}).reduce((sum, value) => sum + Number(value), 0))
function optionCount(option) { return Number(question.value?.answer_distribution?.[option] ?? 0) }
function optionPercent(option) { return totalQuestionAnswers.value ? Math.round(optionCount(option) / totalQuestionAnswers.value * 100) : 0 }
const dashOffset = computed(() => {
  const total = question.value?.duration_seconds ?? 1
  return circumference * (1 - timeLeft.value / total)
})
const timerColor = computed(() => {
  const total = question.value?.duration_seconds ?? 1
  const ratio = timeLeft.value / total
  if (ratio > 0.5) return '#00C65A'
  if (ratio > 0.25) return '#F7B600'
  return '#C8102E'
})

let triviaTimer = null
let clockTimer = null

onMounted(() => { clockTimer = setInterval(() => { nowTick.value = Date.now() }, 1000) })

watch(question, (q) => {
  clearInterval(triviaTimer)
  if (q && phase.value === 'trivia_live') {
    timeLeft.value = q.seconds_remaining ?? q.duration_seconds
    triviaTimer = setInterval(() => {
      if (timeLeft.value > 0) timeLeft.value--
      else clearInterval(triviaTimer)
    }, 1000)
  }
}, { immediate: true })

onUnmounted(() => {
  clearInterval(triviaTimer)
  clearInterval(clockTimer)
  clearTimeout(linkMessageTimer)
  clearInterval(predictionFeedTimer)
  document.removeEventListener('fullscreenchange', syncFullscreen)
})
</script>

<style scoped>
.screen-brand-bg {
  background-color: #03130b;
  background-image:
    linear-gradient(90deg, rgba(2, 18, 10, .92), rgba(2, 18, 10, .78) 55%, rgba(2, 18, 10, .44)),
    url('/images/backgrounds/event-landscape.webp');
  background-size: cover;
  background-position: center;
}
.ticker-enter-active, .ticker-leave-active { transition: all 0.5s ease; }
.ticker-enter-from { opacity: 0; transform: translateY(14px); }
.ticker-leave-to   { opacity: 0; transform: translateY(-14px); }
.prediction-feed-scroll { scrollbar-width: thin; scrollbar-color: rgba(53,208,111,.65) rgba(255,255,255,.06); }
.prediction-feed-scroll::-webkit-scrollbar { width: 7px; }
.prediction-feed-scroll::-webkit-scrollbar-track { background: rgba(255,255,255,.05); border-radius: 999px; }
.prediction-feed-scroll::-webkit-scrollbar-thumb { background: rgba(53,208,111,.65); border-radius: 999px; }
.phase-enter { animation: phase-in .45s cubic-bezier(.2,.8,.2,1) both; }
.count-enter-active, .count-leave-active { transition: all .2s ease; }
.count-enter-from { opacity: 0; transform: translateY(12px) scale(1.15); }
.count-leave-to { opacity: 0; transform: translateY(-10px); }
@media (max-width: 900px), (orientation: portrait) {
  .broadcast-grid { grid-template-columns: minmax(11rem,.8fr) minmax(17rem,1.2fr); align-items: center; }
  .broadcast-latest { grid-column: 1 / -1; }
  .broadcast-latest ol { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
}
@keyframes phase-in {
  from { opacity: 0; transform: translateY(18px) scale(.985); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
@media (prefers-reduced-motion: reduce) {
  .phase-enter { animation: none; }
  .ticker-enter-active, .ticker-leave-active, .count-enter-active, .count-leave-active { transition: none; }
}
</style>

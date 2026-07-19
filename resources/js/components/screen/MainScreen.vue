<template>
  <!-- screen-root fixes the view to the viewport and prevents any scrolling on TV browsers -->
  <div class="screen-root screen-brand-bg text-white font-display select-none flex flex-col">

    <div class="fixed right-3 top-3 z-50 flex items-center gap-2 print:hidden">
      <span v-if="linkMessage" class="rounded-full bg-black/70 px-3 py-2 text-xs font-medium text-white backdrop-blur">{{ linkMessage }}</span>
      <button type="button" @click="toggleFullscreen"
        :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
        :aria-label="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
        class="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 bg-black/55 text-base text-white backdrop-blur hover:bg-black/75 focus:outline-none focus:ring-2 focus:ring-visa-gold">
        {{ isFullscreen ? '✕' : '⛶' }}
      </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         LOBBY / PREDICTIONS
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-if="['lobby', 'predictions_open', 'predictions_closed'].includes(phase)">
      <div class="phase-enter flex-1 flex min-h-0 flex-col px-6 pb-20 pt-5 lg:px-14 lg:pb-24 lg:pt-8 xl:pt-14 2xl:pt-20">
        <header class="screen-lobby-header mx-auto flex w-full max-w-[96rem] items-center justify-between gap-8">
          <div class="min-w-0 text-left">
            <h1 class="screen-title">FIFA World Cup 2026™ Final Match</h1>
          </div>
          <div class="flex shrink-0 items-center border-l border-white/18 pl-8">
            <img src="/images/visa-logo.svg" alt="Visa"
              class="object-contain drop-shadow-lg" style="height: clamp(1.7rem, 2.35vw, 3.2rem)" />
          </div>
        </header>

        <div class="broadcast-grid mx-auto grid min-h-0 w-full max-w-[96rem] flex-1 grid-cols-[minmax(15rem,.78fr)_minmax(22rem,1.1fr)_minmax(19rem,.95fr)] items-center gap-6 pt-5 lg:gap-8 lg:pt-8">
          <div class="screen-panel screen-qr-panel flex flex-col items-center justify-center">
            <div class="rounded-[1.25rem] bg-white p-1.5 shadow-2xl lg:p-2">
              <canvas ref="qrCanvas" :width="qrSize" :height="qrSize"></canvas>
            </div>
            <p class="mt-4 font-medium leading-tight text-white" style="font-size: clamp(1.2rem, 2.1vw, 2.7rem)">
              Scan to play
            </p>
            <p class="mt-1 text-white/72" style="font-size: clamp(0.8rem, 1.2vw, 1.6rem)">
              Register &amp; make your predictions
            </p>
            <p class="mt-5 rounded-full border border-white/12 bg-white/6 px-4 py-2 font-medium text-white/78" style="font-size: clamp(.7rem, .9vw, .95rem)">
              {{ playerCount }} players registered
            </p>
          </div>

          <div class="flex min-w-0 flex-col items-center text-center">
            <div class="screen-status-card rounded-2xl border px-7 py-5"
              :class="phase === 'predictions_closed' ? 'border-orange-400/30 bg-orange-400/10' : 'border-visa-gold/30 bg-visa/20'">
              <p class="font-medium uppercase tracking-widest"
                :class="phase === 'predictions_closed' ? 'text-orange-400' : 'text-visa-gold'"
                style="font-size: clamp(.65rem, 1vw, 1rem)">
                {{ phase === 'lobby' ? 'Waiting to start' : phase === 'predictions_closed' ? 'Predictions closed' : 'Predictions open' }}
              </p>
              <p class="mt-1 font-medium tabular-nums text-white" style="font-size: clamp(1.25rem, 2.2vw, 2.8rem)">
                {{ phase === 'lobby' ? 'Scan to join' : `${predictionCount} locked in` }}
              </p>
            </div>

            <div v-if="phase === 'predictions_open'" class="screen-panel mt-4 max-w-xl px-5 py-4 text-left">
              <p class="font-medium uppercase tracking-widest text-visa-gold" style="font-size: clamp(.6rem,.9vw,.9rem)">Make match prediction on your phone</p>
              <p class="mt-2 text-center font-semibold text-white" style="font-size: clamp(.78rem,1vw,1.05rem)">You'll predict:</p>
              <div class="mt-2 grid grid-cols-2 gap-x-5 gap-y-1.5 text-white/82" style="font-size: clamp(.72rem,.92vw,1rem)">
                <p>Correct score</p>
                <p>First team to score</p>
                <p>First goalscorer</p>
                <p>First goal minute</p>
                <p>Half-time result</p>
                <p>Full-time result</p>
              </div>
            </div>

            <div v-if="match.kickoff_at && kickoffCountdown" class="screen-countdown mt-4 w-full max-w-xl px-7 py-5">
              <p class="uppercase tracking-widest text-white/68" style="font-size: clamp(.6rem, 1vw, 1rem)">Kick-off countdown</p>
              <p class="font-medium tabular-nums text-white" style="font-size: clamp(1.45rem, 2.65vw, 3.35rem)">{{ kickoffCountdown }}</p>
              <p class="text-white/68" style="font-size: clamp(.6rem, .9vw, .9rem)">{{ match.venue }}</p>
            </div>
          </div>

          <aside class="broadcast-latest screen-panel min-w-0 p-4 text-left lg:p-5">
            <div class="mb-3 flex items-center justify-between gap-3 border-b border-white/10 pb-3">
              <div>
                <p class="font-medium uppercase tracking-widest text-white" style="font-size: clamp(.65rem,1vw,1rem)">Latest locked in</p>
                <p class="mt-0.5 text-white/62" style="font-size: clamp(.55rem,.8vw,.8rem)">{{ predictionFeed.length }} players · newest first</p>
              </div>
              <span class="h-2.5 w-2.5 shrink-0 animate-pulse rounded-full bg-visa-gold"></span>
            </div>
            <TransitionGroup v-if="predictionFeed.length" name="ticker" tag="ol" class="prediction-feed-scroll max-h-[42vh] space-y-2 overflow-y-auto overscroll-contain pr-1">
              <li v-for="(entry, idx) in predictionFeed" :key="entry.id"
                class="flex min-w-0 items-center gap-3 rounded-xl border border-white/8 bg-white/7 px-3 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-visa/30 text-xs font-medium text-visa-gold">{{ idx + 1 }}</span>
                <div class="min-w-0">
                  <p class="truncate font-medium text-white" style="font-size: clamp(.75rem,1.2vw,1.3rem)">{{ entry.nickname }}</p>
                  <p class="text-white/58" style="font-size: clamp(.55rem,.75vw,.75rem)">Prediction locked</p>
                </div>
              </li>
            </TransitionGroup>
            <p v-else class="py-8 text-center text-sm text-white/58">Waiting for the first prediction...</p>
          </aside>
        </div>
      </div>
    </template>
    <template v-else-if="phase === 'trivia_ready'">
      <div class="phase-enter flex-1 flex min-h-0 flex-col px-6 pb-20 pt-5 lg:px-14 lg:pb-24 lg:pt-10 xl:pt-14">
        <header class="screen-lobby-header mx-auto flex w-full max-w-[96rem] items-center justify-between gap-8">
          <div class="min-w-0 text-left">
            <h1 class="screen-title">Trivia starts soon</h1>
          </div>
          <div class="flex shrink-0 items-center border-l border-white/18 pl-8">
            <img src="/images/visa-logo.svg" alt="Visa"
              class="object-contain drop-shadow-lg" style="height: clamp(1.7rem, 2.35vw, 3.2rem)" />
          </div>
        </header>

        <div class="mx-auto grid min-h-0 w-full max-w-[96rem] flex-1 grid-cols-1 items-center gap-8 pt-8 lg:grid-cols-[minmax(18rem,.85fr)_minmax(22rem,1.15fr)] lg:gap-12">
          <div class="screen-panel screen-qr-panel flex flex-col items-center justify-center">
            <div class="rounded-[1.25rem] bg-white p-1.5 shadow-2xl lg:p-2">
              <canvas ref="qrCanvas" :width="qrSize" :height="qrSize"></canvas>
            </div>
            <p class="mt-4 font-medium leading-tight text-white" style="font-size: clamp(1.25rem, 2.3vw, 3rem)">
              Scan to play
            </p>
            <p class="mt-5 rounded-full border border-white/12 bg-white/6 px-4 py-2 font-medium text-white/78" style="font-size: clamp(.75rem, 1vw, 1.05rem)">
              {{ playerCount }} players registered
            </p>
          </div>

          <div class="min-w-0">
            <div class="screen-panel px-7 py-7 lg:px-10 lg:py-9">
              <p class="font-medium uppercase tracking-widest text-visa-gold" style="font-size: clamp(.7rem, 1vw, 1.15rem)">
                {{ activeRound.number === 1 ? 'Before question one' : 'Before the next round' }}
              </p>
              <div class="mt-4 rounded-2xl border border-visa-gold/35 bg-visa-gold/10 px-5 py-4">
                <p class="font-medium uppercase tracking-widest text-visa-gold" style="font-size: clamp(.65rem, .9vw, 1rem)">
                  Round {{ activeRound.number }} coming
                </p>
                <p class="mt-1 font-semibold text-white" style="font-size: clamp(1.25rem, 2.3vw, 3rem)">
                  {{ activeRound.label }}
                </p>
              </div>
              <div class="mt-6 grid gap-4">
                <p class="rounded-2xl border border-white/10 bg-white/7 px-5 py-4 font-semibold text-white" style="font-size: clamp(1rem, 1.85vw, 2.35rem)">
                  New players: register
                </p>
                <p class="rounded-2xl border border-white/10 bg-white/7 px-5 py-4 font-semibold text-white" style="font-size: clamp(1rem, 1.85vw, 2.35rem)">
                  Already registered: sign in
                </p>
                <p class="rounded-2xl border border-visa-gold/35 bg-visa-gold/10 px-5 py-4 font-semibold text-white" style="font-size: clamp(.95rem, 1.55vw, 2rem)">
                  When a question appears on your phone, tap your answer
                </p>
                <p class="px-5 pt-1 font-medium text-white/70" style="font-size: clamp(.75rem, 1.05vw, 1.2rem)">
                  The MC will cue each question.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
    <template v-else-if="phase === 'trivia_loading' || (phase === 'trivia_live' && !question)">
      <div class="phase-enter flex-1 flex flex-col items-center justify-center px-8 pb-20 pt-8 text-center lg:px-16">
        <img src="/images/visa-logo.svg" alt="Visa" class="mb-8 h-12 object-contain drop-shadow-lg lg:h-16" />
        <p class="font-medium uppercase tracking-[.22em] text-visa-gold" style="font-size: clamp(.8rem, 1.35vw, 1.6rem)">
          Round {{ activeRound.number }} · {{ activeRound.label }}
        </p>
        <h2 class="mt-5 font-semibold text-white" style="font-size: clamp(2.4rem, 6vw, 7rem)">
          Loading next question
        </h2>
        <p class="mt-5 max-w-3xl text-white/70" style="font-size: clamp(1rem, 1.8vw, 2.2rem)">
          Keep your phone ready. The MC will cue the question.
        </p>
        <div class="mt-10 flex items-center gap-3" aria-hidden="true">
          <span class="h-3 w-3 animate-pulse rounded-full bg-visa-gold"></span>
          <span class="h-3 w-3 animate-pulse rounded-full bg-visa-gold [animation-delay:.15s]"></span>
          <span class="h-3 w-3 animate-pulse rounded-full bg-visa-gold [animation-delay:.3s]"></span>
        </div>
      </div>
    </template>
    <template v-else-if="phase === 'trivia_live' && question">
      <div class="phase-enter flex-1 flex flex-col px-8 lg:px-16 pt-6 lg:pt-10 pb-4">

        <!-- Top bar: double-points badge + huge countdown -->
        <div class="flex items-center justify-between mb-6 lg:mb-8 flex-shrink-0">
          <div class="flex items-center gap-4">
          <span class="rounded-full border border-white/10 bg-black/20 px-4 py-2 font-medium uppercase tracking-widest text-gray-300"
            style="font-size: clamp(0.65rem, 1.1vw, 1.1rem)">
            Round {{ activeRound.number }} · {{ activeRoundTitle }} · Question {{ round.current }} / {{ round.total }}
          </span>
          <span v-if="question.is_double_points"
            class="bg-visa-gold text-black font-semibold uppercase tracking-widest animate-pulse rounded-full px-5 py-2"
            style="font-size: clamp(0.75rem, 1.5vw, 1.5rem)">
            ⚡ DOUBLE POINTS ⚡
          </span>
          <span v-else class="text-white/62" style="font-size: clamp(0.75rem, 1.2vw, 1.2rem)">
            Question {{ question.order_index }}
          </span>
          </div>

          <!-- Countdown ring — viewport-sized so it's always visible from the back row -->
          <div class="flex items-center gap-4 lg:gap-7">
            <img src="/images/visa-logo.svg" alt="Visa" class="object-contain drop-shadow-lg"
              style="height: clamp(1.5rem, 2.2vw, 3.2rem)" />
            <div class="text-right">
              <Transition name="count" mode="out-in">
                <p :key="question.answer_count" class="font-semibold text-visa-gold tabular-nums" style="font-size: clamp(1.4rem, 3vw, 3.5rem)">{{ question.answer_count ?? 0 }}</p>
              </Transition>
              <p class="text-white/62 uppercase tracking-wider" style="font-size: clamp(.55rem, .9vw, .9rem)">Answers live</p>
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
            <span class="absolute inset-0 flex items-center justify-center font-semibold"
              :class="timeLeft <= 5 ? 'text-red-400 animate-pulse' : 'text-white'"
              style="font-size: clamp(1.5rem, 4vw, 5rem)">
              {{ timeLeft }}
            </span>
          </div>
          </div>
        </div>

        <!-- Question text — the most important thing on the screen -->
        <p class="font-medium text-center leading-tight flex-1 flex items-center justify-center"
          style="font-size: clamp(1.5rem, 3.5vw, 5rem)">
          {{ question.text }}
        </p>

        <!-- Answer options grid -->
        <div class="grid gap-4 lg:gap-5 mt-6 flex-shrink-0"
          :class="question.options.length === 2 ? 'grid-cols-2' : 'grid-cols-2'">
          <div v-for="(opt, idx) in question.options" :key="idx"
            class="bg-white/10 border border-white/20 rounded-2xl flex items-center gap-4 px-5 lg:px-8 py-4 lg:py-6">
            <span class="rounded-full bg-white/20 font-semibold flex items-center justify-center flex-shrink-0"
              style="font-size: clamp(0.875rem, 1.8vw, 2rem);
                     width: clamp(2rem, 4vw, 4.5rem);
                     height: clamp(2rem, 4vw, 4.5rem)">
              {{ optLabels[idx] }}
            </span>
            <span class="font-medium leading-snug" style="font-size: clamp(0.875rem, 2vw, 2.5rem)">
              {{ opt }}
            </span>
          </div>
        </div>

        <LiveLeaderboardStrip v-if="leaderboard.length" :entries="leaderboard" class="mt-4 flex-shrink-0" />

      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         TRIVIA REVEAL
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else-if="phase === 'trivia_reveal' && question">
      <div class="relative flex-1 flex flex-col px-8 lg:px-16 pt-8 pb-4">
        <img src="/images/visa-logo.svg" alt="Visa" class="absolute right-8 top-8 object-contain drop-shadow-lg lg:right-16 lg:top-10"
          style="height: clamp(1.5rem, 2.2vw, 3.2rem)" />
        <p class="text-center text-white/70 mb-4 flex-shrink-0"
          style="font-size: clamp(0.875rem, 1.5vw, 1.8rem)">
          Answer Revealed
        </p>
        <p class="text-center font-medium mb-6 flex-shrink-0"
          style="font-size: clamp(1.25rem, 2.5vw, 3.5rem)">
          {{ question.text }}
        </p>

        <div class="grid gap-4 mb-8 flex-shrink-0"
          :class="question.options.length === 2 ? 'grid-cols-2' : 'grid-cols-2'">
          <div v-for="(opt, idx) in question.options" :key="idx"
            :class="opt === question.correct_answer
              ? 'bg-visa-gold/30 border-visa-gold text-white'
              : 'bg-white/5 border-white/10 text-white/58'"
            class="border-2 rounded-2xl flex items-center gap-4 px-5 lg:px-8 py-4 lg:py-5 transition-all">
            <span class="text-2xl lg:text-3xl flex-shrink-0">
              {{ opt === question.correct_answer ? '✅' : '' }}
            </span>
            <span class="font-medium leading-snug"
              :class="opt === question.correct_answer ? 'font-semibold text-white' : ''"
              style="font-size: clamp(0.875rem, 2vw, 2.5rem)">
              {{ opt }}
            </span>
            <div class="ml-auto shrink-0 text-right">
              <p class="font-medium tabular-nums" :class="opt === question.correct_answer ? 'text-visa-gold' : 'text-white/58'"
                style="font-size: clamp(.8rem, 1.5vw, 1.7rem)">{{ optionCount(opt) }}</p>
              <p class="text-white/52" style="font-size: clamp(.55rem, .8vw, .8rem)">{{ optionPercent(opt) }}%</p>
            </div>
          </div>
        </div>

        <div class="flex-1 overflow-hidden">
          <Leaderboard :entries="leaderboard" :title="`${activeRoundTitle} standings`" compact />
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         TRIVIA COMPLETE
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else-if="phase === 'trivia_complete'">
      <div class="phase-enter flex-1 flex flex-col px-8 lg:px-16 pt-6 lg:pt-8 pb-4 overflow-hidden">
        <h2 class="flex-shrink-0 text-center font-light uppercase tracking-[.15em] text-visa-gold mb-3 lg:mb-4"
          style="font-size: clamp(1.1rem, 2.2vw, 2.4rem)">
          ROUND {{ activeRound.number }} COMPLETE
        </h2>
        <p class="-mt-2 mb-4 flex-shrink-0 text-center font-medium uppercase tracking-[.18em] text-white/72"
          style="font-size: clamp(.75rem, 1.25vw, 1.35rem)">
          {{ activeRoundTitle }}
        </p>
        <div class="min-h-0 flex-1">
          <Leaderboard :entries="activeRoundLeaderboard" :title="`${activeRoundTitle} standings`" />
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         MATCH ENDED / PREDICTION REVEAL
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else-if="['match_ended', 'prediction_reveal'].includes(phase)">
      <div class="flex-1 flex flex-col px-8 lg:px-16 pt-6 lg:pt-8 pb-4 overflow-hidden">
        <h2 class="flex-shrink-0 text-center font-light uppercase tracking-[.15em] text-visa-gold mb-3 lg:mb-4"
          style="font-size: clamp(1.1rem, 2.2vw, 2.4rem)">
          PREDICTIONS LEADERBOARD
        </h2>
        <div class="min-h-0 flex-1">
          <Leaderboard :entries="leaderboard" title="Prediction standings" />
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         FALLBACK
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else>
      <div class="flex-1 flex items-center justify-center">
        <p class="text-white font-medium italic uppercase" style="font-size: clamp(1.5rem, 4vw, 5rem)">
          <span>Predict and Win</span>
        </p>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         BOTTOM BRAND STRIP
    ══════════════════════════════════════════════════════════════════════ -->
    <div class="screen-footer flex-shrink-0 bg-gradient-to-r from-visa via-[#1434CB] to-visa
                flex items-center justify-center gap-6 lg:gap-12 px-8"
      style="height: clamp(2.5rem, 4vh, 5rem)">
      <img src="/images/visa-logo.svg" alt="Visa"
        class="object-contain opacity-100" style="height: clamp(1rem, 2.1vh, 2.2rem)" />
      <span class="text-white/30">·</span>
      <span class="text-white font-medium tracking-widest opacity-80"
        style="font-size: clamp(0.6rem, 1.2vw, 1.2rem)">ARGENTINA vs SPAIN · FINAL</span>
    </div>

    <!-- Connection error -->
    <div v-if="error"
      class="fixed bottom-20 right-4 bg-red-600 text-white text-xs px-3 py-1.5 rounded-full opacity-80">
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
import LiveLeaderboardStrip from './LiveLeaderboardStrip.vue'

const { phase, question, leaderboard, leaderboards, playerCount, predictionCount, recentPredictions, match, round, activeRound, error } = useEventState(1500)

// ── QR Code — size scales with viewport, capped for readability ───────────────
const qrCanvas = ref(null)
// Guests join via the same host that serves this screen. APP_URL (the meta tag)
// may be a dev hostname that venue phones cannot resolve — never encode it in the QR.
const appUrl   = window.location.origin
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

async function toggleFullscreen() {
  try {
    if (document.fullscreenElement) await document.exitFullscreen()
    else await document.documentElement.requestFullscreen()
  } catch {
    linkMessage.value = 'Fullscreen is blocked by this browser'
    clearTimeout(linkMessageTimer)
    linkMessageTimer = setTimeout(() => { linkMessage.value = '' }, 2500)
  }
}

function syncFullscreen() { isFullscreen.value = !!document.fullscreenElement }

// QR canvas: ~28% of the smaller viewport dimension, min 200, max 420 — sized
// for scanning off a TV/monitor rather than a phone screen
const qrSize = computed(() =>
  Math.min(Math.max(Math.round(Math.min(window.innerWidth, window.innerHeight) * 0.28), 200), 420)
)

onMounted(() => {
  document.addEventListener('fullscreenchange', syncFullscreen)
  loadPredictionFeed()
  predictionFeedTimer = setInterval(loadPredictionFeed, 3000)
})

// The QR canvas sits behind a phase v-if, so Vue destroys/recreates the
// <canvas> element every time the screen leaves and returns to the lobby
// group — redraw whenever that happens, not just on the initial mount.
watch(qrCanvas, (canvas) => {
  if (canvas) {
    QRCode.toCanvas(canvas, appUrl, {
      width:  qrSize.value,
      margin: 1,
      color: { dark: '#000000', light: '#ffffff' },
    })
  }
}, { immediate: true })

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
const activeRoundTitle = computed(() => question.value?.round_title ?? activeRound.value?.label ?? 'Trivia')
const fifaLeaderboard = computed(() => leaderboards.value?.fifa ?? [])
const visaLeaderboard = computed(() => leaderboards.value?.visa ?? [])
const activeRoundLeaderboard = computed(() => {
  const category = question.value?.category ?? activeRound.value?.category
  if (category === 'fifa_world_cup') return fifaLeaderboard.value
  if (category === 'visa') return visaLeaderboard.value
  return leaderboards.value?.trivia ?? leaderboard.value
})
function optionCount(option) { return Number(question.value?.answer_distribution?.[option] ?? 0) }
function optionPercent(option) { return totalQuestionAnswers.value ? Math.round(optionCount(option) / totalQuestionAnswers.value * 100) : 0 }
const dashOffset = computed(() => {
  const total = question.value?.duration_seconds ?? 1
  return circumference * (1 - timeLeft.value / total)
})
const timerColor = computed(() => {
  const total = question.value?.duration_seconds ?? 1
  const ratio = timeLeft.value / total
  if (ratio > 0.5) return '#F7B600'
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
  position: relative;
  isolation: isolate;
  min-height: 100dvh;
  background-color: #1434CB;
  background-image:
    linear-gradient(180deg, rgba(5, 15, 70, .14), rgba(5, 15, 70, .45)),
    linear-gradient(118deg, transparent 0 58%, rgba(255, 255, 255, .1) 58% 68%, transparent 68%),
    linear-gradient(120deg, rgba(255, 255, 255, .07) 0 1px, transparent 1px 118px),
    linear-gradient(108deg, #1434CB 0%, #183AD0 48%, #0A1F8F 100%);
  background-size: cover, cover, 118px 118px, cover;
  background-position: center;
}
.screen-brand-bg::before {
  content: '';
  position: fixed;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  background:
    linear-gradient(104deg, transparent 0 49%, rgba(255,255,255,.15) 49.2% 51%, transparent 51.2%),
    linear-gradient(153deg, transparent 0 60%, rgba(255,255,255,.08) 60.1% 61.5%, transparent 61.7%),
    linear-gradient(18deg, transparent 0 72%, rgba(247,182,0,.28) 72.2% 72.55%, transparent 72.8%),
    linear-gradient(90deg, rgba(6,22,95,.5) 0%, transparent 42%),
    linear-gradient(0deg, rgba(3,12,57,.18) 0%, transparent 24%);
}
.screen-footer {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 30;
  box-shadow: 0 -18px 45px rgba(0,0,0,.2);
}
.screen-title {
  margin-top: .25rem;
  color: #fff;
  font-size: clamp(2.15rem, 4.45vw, 5.35rem);
  font-weight: 520;
  line-height: 1;
  letter-spacing: 0;
  text-shadow: 0 18px 52px rgba(0,0,0,.28);
}
.screen-subtitle {
  margin-top: .85rem;
  color: #f7b600;
  font-size: clamp(1rem, 1.75vw, 2rem);
  font-weight: 600;
  letter-spacing: .08em;
  text-transform: uppercase;
}
.screen-lobby-header {
  min-height: clamp(4.5rem, 12vh, 8rem);
}
.screen-panel {
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 1.25rem;
  background: linear-gradient(145deg, rgba(6,22,95,.62), rgba(4,12,52,.78));
  box-shadow: 0 24px 70px rgba(0,0,0,.25), inset 0 1px 0 rgba(255,255,255,.06);
  backdrop-filter: blur(14px);
}
.screen-qr-panel {
  min-height: clamp(20rem, 42vh, 31rem);
  padding: clamp(1.25rem, 2vw, 2rem);
}
.screen-status-card,
.screen-countdown {
  box-shadow: 0 22px 54px rgba(0,0,0,.24), inset 0 1px 0 rgba(255,255,255,.06);
  backdrop-filter: blur(14px);
}
.screen-countdown {
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 1.25rem;
  background: linear-gradient(145deg, rgba(6,22,95,.64), rgba(4,12,52,.76));
}
.ticker-enter-active, .ticker-leave-active { transition: all 0.5s ease; }
.ticker-enter-from { opacity: 0; transform: translateY(14px); }
.ticker-leave-to   { opacity: 0; transform: translateY(-14px); }
.prediction-feed-scroll { scrollbar-width: thin; scrollbar-color: rgba(247,182,0,.65) rgba(255,255,255,.06); }
.prediction-feed-scroll::-webkit-scrollbar { width: 7px; }
.prediction-feed-scroll::-webkit-scrollbar-track { background: rgba(255,255,255,.05); border-radius: 999px; }
.prediction-feed-scroll::-webkit-scrollbar-thumb { background: rgba(247,182,0,.65); border-radius: 999px; }
.phase-enter { animation: phase-in .45s cubic-bezier(.2,.8,.2,1) both; }
.count-enter-active, .count-leave-active { transition: all .2s ease; }
.count-enter-from { opacity: 0; transform: translateY(12px) scale(1.15); }
.count-leave-to { opacity: 0; transform: translateY(-10px); }
@media (max-width: 900px), (orientation: portrait) {
  .screen-lobby-header {
    align-items: flex-start;
    gap: 1rem;
  }
  .screen-title {
    font-size: clamp(2rem, 6vw, 4.5rem);
  }
  .broadcast-grid {
    grid-template-columns: minmax(12rem,.85fr) minmax(17rem,1.15fr);
    align-items: center;
  }
  .broadcast-latest {
    grid-column: 1 / -1;
  }
  .broadcast-latest ol {
    display: grid;
    grid-template-columns: repeat(3,minmax(0,1fr));
    gap: .5rem;
  }
  .screen-qr-panel {
    min-height: auto;
    padding: 1rem;
  }
}
@media (max-height: 760px) and (orientation: landscape) {
  .screen-lobby-header {
    min-height: 4.25rem;
  }
  .screen-title {
    font-size: clamp(2rem, 4vw, 4.6rem);
  }
  .screen-qr-panel {
    min-height: 18rem;
  }
}
@media (max-width: 640px) {
  .screen-lobby-header {
    flex-direction: column;
  }
  .screen-lobby-header > div:last-child {
    border-left: 0;
    padding-left: 0;
  }
  .broadcast-grid {
    grid-template-columns: 1fr;
    overflow-y: auto;
  }
  .broadcast-latest ol {
    grid-template-columns: 1fr;
  }
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

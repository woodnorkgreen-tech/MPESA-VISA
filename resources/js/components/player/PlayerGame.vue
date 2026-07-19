<template>
  <div class="event-surface min-h-dvh text-white flex flex-col" :class="{ 'lobby-bg': ['lobby', 'trivia_ready'].includes(phase) }">

    <!-- Not registered guard -->
    <div v-if="!playerId" class="flex-1 flex items-center justify-center p-6 text-center">
      <div>
        <p class="text-gray-400 mb-4">Session expired or not registered.</p>
        <a href="/" class="text-visa-gold underline text-lg">Register / Login →</a>
      </div>
    </div>

    <template v-else>

      <!-- ── Sticky player header ──────────────────────────────────────────── -->
      <header class="flex items-center justify-between gap-2 bg-visa/95 px-3 py-2 backdrop-blur-xl border-b border-white/10 flex-shrink-0 pt-safe sm:px-6 sm:py-3">
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
          <img src="/images/visa-logo.svg" alt="Visa" class="h-4 shrink-0 object-contain sm:h-6" />
          <span class="hidden h-5 w-px bg-white/25 sm:block" aria-hidden="true"></span>
          <span class="truncate text-[10px] font-black uppercase tracking-[.14em] text-white/80 sm:text-sm sm:tracking-[.18em]">
            <span class="sm:hidden">FWC Final</span>
            <span class="hidden sm:inline">FIFA World Cup 2026™ Final Match</span>
          </span>
        </div>
        <div class="flex min-w-0 shrink-0 items-center gap-1.5 sm:gap-3">
          <span v-if="adminPreview" class="rounded-full bg-purple-500/20 px-2 py-1 text-[10px] font-bold text-purple-200 sm:px-2.5 sm:text-xs">MC Preview</span>
          <span v-else class="max-w-[5.5rem] truncate text-xs font-semibold text-white/85 sm:max-w-[12rem] sm:text-base">{{ playerNickname }}</span>
          <button v-if="!adminPreview" @click="signOut"
            class="rounded-md border border-black bg-black px-2 py-1.5 text-[10px] font-bold text-white transition hover:border-gray-900 hover:bg-gray-900 sm:rounded-lg sm:px-3 sm:text-sm">
            <span class="sm:hidden">Out</span>
            <span class="hidden sm:inline">Sign Out</span>
          </button>
        </div>
      </header>

      <!-- ── Loading ──────────────────────────────────────────────────────── -->
      <div v-if="loading" class="flex-1 flex items-center justify-center">
        <div class="text-gray-500 text-lg">Connecting…</div>
      </div>

      <!-- ── Lobby / waiting ─────────────────────────────────────────────── -->
      <div v-else-if="phase === 'lobby'"
        class="flex-1 flex flex-col items-center justify-center p-6 sm:p-10 text-center pb-safe">
        <div class="lobby-card glass-card w-full max-w-sm rounded-3xl px-7 py-10 sm:px-10 sm:py-12">

          <span class="inline-flex items-center gap-2 rounded-full bg-visa/15 border border-visa/30 px-3 py-1 mb-7">
            <span class="h-1.5 w-1.5 rounded-full bg-visa-gold animate-pulse" aria-hidden="true"></span>
            <span class="brand-kicker">Predict and Win</span>
          </span>

          <div class="relative w-20 h-20 mx-auto mb-7" aria-hidden="true">
            <div class="absolute inset-0 rounded-full border-4 border-white/10"></div>
            <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-visa-gold animate-spin"></div>
            <span class="absolute inset-0 flex items-center justify-center text-3xl">⚽</span>
          </div>

          <h2 class="text-2xl sm:text-3xl font-bold text-white mb-2">Waiting to start</h2>
          <p class="text-gray-300 text-base sm:text-lg leading-relaxed">Keep this page open. The game will update automatically.</p>

          <div class="mt-7 inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-2">
            <span class="h-2 w-2 rounded-full bg-visa-gold animate-pulse" aria-hidden="true"></span>
            <span class="text-sm text-gray-300"><strong class="text-white font-bold">{{ playerCount }}</strong> players joined</span>
          </div>
        </div>
      </div>

      <!-- ── Trivia check-in ─────────────────────────────────────────────── -->
      <div v-else-if="phase === 'trivia_ready'"
        class="flex-1 flex flex-col items-center justify-center p-6 sm:p-10 text-center pb-safe">
        <div class="lobby-card glass-card w-full max-w-sm rounded-3xl px-7 py-10 sm:px-10 sm:py-12">
          <img src="/images/visa-logo.svg" alt="Visa"
            class="mx-auto mb-7 h-8 max-w-[52vw] object-contain drop-shadow-2xl sm:h-10" />
          <p class="brand-kicker mb-2">Round {{ activeRound.number }} coming</p>
          <h2 class="text-2xl sm:text-3xl font-bold text-visa-gold mb-3">You're ready</h2>
          <p class="-mt-1 mb-4 text-lg font-bold text-white sm:text-xl">{{ activeRound.label }}</p>
          <p class="text-gray-300 text-base sm:text-lg leading-relaxed">
            When a question appears on your phone, tap your answer.
          </p>
          <p class="mt-3 text-sm text-gray-400 sm:text-base">
            The MC will cue each question.
          </p>

          <div class="mt-7 inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-2">
            <span class="h-2 w-2 rounded-full bg-visa-gold animate-pulse" aria-hidden="true"></span>
            <span class="text-sm text-gray-300">Keep this page open</span>
          </div>
        </div>
      </div>

      <!-- ── Predictions form ────────────────────────────────────────────── -->
      <PredictionsForm
        v-else-if="phase === 'predictions_open'"
        :player-id="playerId"
        :match="match"
        :read-only="adminPreview" />

      <!-- ── Trivia live ─────────────────────────────────────────────────── -->
      <TriviaQuestion
        v-else-if="phase === 'trivia_live' && question && !question.correct_answer"
        :question="question"
        :round="round"
        :player-id="playerId"
        :read-only="adminPreview"
        :key="question.id"
        @answered="onAnswered" />

      <!-- ── Trivia reveal ───────────────────────────────────────────────── -->
      <div v-else-if="['trivia_live', 'trivia_reveal'].includes(phase) && question?.correct_answer"
        class="flex-1 flex flex-col items-center justify-center p-6 sm:p-10 text-center pb-safe">
        <div v-if="answerResultLoading" class="text-gray-400 text-lg">Checking your answer…</div>
        <div v-else class="glass-card w-full max-w-lg rounded-3xl p-6 sm:p-9">
          <div v-if="answerResultKnown" class="text-5xl sm:text-6xl mb-3" aria-hidden="true">{{ lastAnswerCorrect ? '✓' : '×' }}</div>
          <p v-if="answerResultKnown && lastAnswerCorrect" class="text-visa-gold text-2xl sm:text-3xl font-black">Correct!</p>
          <p v-else-if="answerResultKnown" class="text-red-400 text-2xl sm:text-3xl font-black">Incorrect</p>
          <p v-else class="text-gray-300 text-xl sm:text-2xl font-bold">No answer recorded</p>

          <div class="mt-6 space-y-3 text-left">
            <div v-if="answerResultKnown" class="rounded-xl border px-4 py-3"
              :class="lastAnswerCorrect ? 'border-visa-gold/40 bg-visa/15' : 'border-red-500/35 bg-red-500/10'">
              <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Your choice</p>
              <p class="mt-1 font-bold text-white">{{ lastSelectedAnswer }}</p>
            </div>
            <div class="rounded-xl border border-visa-gold/40 bg-visa/15 px-4 py-3">
              <p class="text-xs font-bold uppercase tracking-widest text-visa-gold">Correct answer</p>
              <p class="mt-1 font-black text-white">{{ question.correct_answer }}</p>
            </div>
          </div>

          <p v-if="answerResultKnown" class="mt-5 font-bold" :class="lastPoints > 0 ? 'text-visa-gold' : 'text-gray-400'">+{{ lastPoints }} points</p>
          <p class="mt-2 text-sm text-gray-500">Your score: <span class="font-bold text-white">{{ playerScore }}</span> pts</p>
        </div>
      </div>

      <!-- ── Trivia complete ─────────────────────────────────────────────── -->
      <div v-else-if="phase === 'trivia_complete'"
        class="flex-1 flex flex-col items-center justify-center p-6 sm:p-10 text-center pb-safe">
        <img src="/images/visa-logo.svg" alt="Visa"
          class="mb-7 h-10 max-w-[58vw] object-contain drop-shadow-2xl sm:h-12" />
        <p class="brand-kicker mb-2">Round {{ activeRound.number }} complete</p>
        <h2 class="text-2xl sm:text-3xl font-bold text-visa-gold mb-3">{{ roundRankHeadline }}</h2>
        <p class="-mt-1 mb-3 text-lg font-bold text-white sm:text-xl">{{ activeRound.label }}</p>
        <p class="text-gray-300 text-base sm:text-lg mb-1">
          <strong class="text-white text-3xl sm:text-4xl">{{ playerRoundScore }}</strong> pts
        </p>
        <p class="text-gray-300 text-sm">Watch the big screen for the live leaderboard.</p>
      </div>

      <!-- ── Match ended / prediction reveal ────────────────────────────── -->
      <div v-else-if="['match_ended', 'prediction_reveal'].includes(phase)"
        class="flex-1 flex flex-col items-center justify-center p-6 text-center pb-safe">
        <img src="/images/brand/world-cup-trophy.png" alt="World Cup trophy"
          class="w-32 sm:w-40 max-h-52 object-contain mb-5 drop-shadow-2xl" />
        <h2 class="text-2xl sm:text-3xl font-bold text-visa-gold mb-2">Predictions locked</h2>
        <p class="text-gray-400 text-base">Watch the big screen for the final standings.</p>
      </div>

      <!-- ── Fallback ────────────────────────────────────────────────────── -->
      <div v-else class="flex-1 flex items-center justify-center text-gray-500 text-lg pb-safe">
        Waiting for next round…
      </div>

    </template>

    <!-- Predictions closed notice — fires once when the window shuts while this tab is open -->
    <PlayerModal v-if="showPredictionsClosedModal" @dismiss="showPredictionsClosedModal = false">
      <div class="text-4xl mb-3" aria-hidden="true">🔒</div>
      <h3 class="text-xl font-black text-white mb-2">Predictions are closed</h3>
      <p class="text-gray-300 text-sm sm:text-base">No more edits — the prediction window for this match has ended.</p>
      <button type="button" @click="showPredictionsClosedModal = false"
        class="mt-6 w-full rounded-xl bg-visa px-5 py-3 text-sm font-black text-white transition hover:bg-visa">
        Got it
      </button>
    </PlayerModal>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import { useEventState } from '../../composables/useEventState'
import PredictionsForm from './PredictionsForm.vue'
import TriviaQuestion from './TriviaQuestion.vue'
import PlayerModal from './PlayerModal.vue'

const props = defineProps({ adminPreview: { type: Boolean, default: false } })
const adminPreview = props.adminPreview
const storedPlayerValue = (key) => localStorage.getItem(key) ?? sessionStorage.getItem(key)
const playerId       = ref(adminPreview ? 'preview' : storedPlayerValue('player_id'))
const playerNickname = ref(adminPreview ? 'MC Preview' : (storedPlayerValue('player_nickname') ?? 'Player'))

const { phase, question, playerCount, match, round, activeRound, loading } = useEventState()

const lastAnswerCorrect   = ref(false)
const lastPoints          = ref(0)
const answerResultKnown   = ref(false)
const answerResultLoading = ref(false)
const lastSelectedAnswer  = ref(null)
const playerScore         = ref(parseInt(sessionStorage.getItem('player_score') ?? '0'))
const playerRoundScore    = ref(parseInt(sessionStorage.getItem('player_round_score') ?? '0'))
const playerRoundRank     = ref(parseInt(sessionStorage.getItem('player_round_rank') ?? '0'))
const showPredictionsClosedModal = ref(false)

const roundRankHeadline = computed(() => {
  if (!playerRoundRank.value) return "You're on the board"
  return `You're #${playerRoundRank.value} on the board`
})

function onAnswered({ isCorrect, pointsAwarded, totalScore, roundScore, roundRank, selectedAnswer }) {
  answerResultKnown.value = true
  lastAnswerCorrect.value = isCorrect
  lastPoints.value        = pointsAwarded
  lastSelectedAnswer.value = selectedAnswer ?? lastSelectedAnswer.value
  playerScore.value       = totalScore
  playerRoundScore.value  = roundScore ?? playerRoundScore.value
  playerRoundRank.value   = roundRank ?? playerRoundRank.value
  sessionStorage.setItem('player_score', totalScore)
  sessionStorage.setItem('player_round_score', playerRoundScore.value)
  sessionStorage.setItem('player_round_rank', playerRoundRank.value)
}

async function loadSavedAnswerResult() {
  if (adminPreview || !playerId.value || !question.value?.id) return

  answerResultLoading.value = true
  try {
    const { data } = await axios.get('/api/answers/result', {
      params: { player_id: playerId.value, question_id: question.value.id },
    })
    answerResultKnown.value   = data.answered
    lastSelectedAnswer.value  = data.selected_option ?? null
    lastAnswerCorrect.value   = data.is_correct ?? false
    lastPoints.value          = data.points_awarded ?? 0
    playerScore.value         = data.total_score ?? playerScore.value
    playerRoundScore.value    = data.round_score ?? playerRoundScore.value
    playerRoundRank.value     = data.round_rank ?? playerRoundRank.value
    sessionStorage.setItem('player_score', playerScore.value)
    sessionStorage.setItem('player_round_score', playerRoundScore.value)
    sessionStorage.setItem('player_round_rank', playerRoundRank.value)
  } catch {
    answerResultKnown.value = false
  } finally {
    answerResultLoading.value = false
  }
}

watch([phase, question], ([currentPhase, currentQuestion], [previousPhase, previousQuestion] = []) => {
  const questionChanged = currentQuestion?.id !== previousQuestion?.id
  if (currentPhase === 'trivia_live' && questionChanged) {
    answerResultKnown.value = false
    lastSelectedAnswer.value = null
    playerRoundScore.value = 0
    playerRoundRank.value = 0
    sessionStorage.setItem('player_round_score', '0')
    sessionStorage.setItem('player_round_rank', '0')
  }
  if (currentQuestion?.correct_answer && (questionChanged || !previousQuestion?.correct_answer || currentPhase !== previousPhase)) {
    loadSavedAnswerResult()
  }
}, { immediate: true })

watch(phase, (currentPhase, previousPhase) => {
  if (previousPhase === 'predictions_open' && currentPhase !== 'predictions_open' && !adminPreview) {
    showPredictionsClosedModal.value = true
  }
})

function signOut() {
  for (const key of ['player_id', 'player_nickname', 'player_session_token', 'player_score', 'prediction_submitted', 'last_prediction']) {
    sessionStorage.removeItem(key)
    localStorage.removeItem(key)
  }
  window.location.href = '/'
}
</script>

<style scoped>
/* ── Lobby: Visa-blue event background ───────────────────────────────────── */
.lobby-bg {
  background-color: #1434CB;
}

.lobby-card {
  animation: lobby-in .55s cubic-bezier(.22, 1, .36, 1) both;
}
@keyframes lobby-in {
  from { opacity: 0; transform: translateY(14px) scale(.98); }
  to   { opacity: 1; transform: none; }
}
</style>

<template>
  <div class="flex min-h-0 flex-1 items-start justify-center overflow-y-auto px-3 py-4 pb-safe sm:px-6 sm:py-8">
    <div class="my-auto w-full max-w-xl">
      <header class="mb-4 text-center sm:mb-6">
        <p class="brand-kicker mb-1.5">Before kick-off</p>
        <h2 class="text-2xl font-extrabold text-white sm:text-3xl">Make your prediction</h2>
        <p class="mt-1 text-sm text-gray-400">Four quick steps. You can edit it until predictions close.</p>
        <p class="mx-auto mt-2 max-w-md rounded-full border border-visa-gold/20 bg-visa-gold/10 px-3 py-1.5 text-[11px] font-semibold text-visa-gold">
          Score predictions use 90 minutes + stoppage time. Extra time and penalties do not count.
        </p>
      </header>

      <form @submit.prevent="submit" class="glass-card overflow-hidden rounded-2xl">
        <div class="border-b border-white/10 px-4 pb-3 pt-4 sm:px-7 sm:pt-6">
          <div class="mb-2 flex items-center justify-between text-xs font-bold">
            <span class="uppercase tracking-widest text-safaricom-light">Step {{ step }} of 4</span>
            <span class="text-gray-500">{{ stepTitles[step - 1] }}</span>
          </div>
          <div class="grid grid-cols-4 gap-1.5" aria-label="Prediction progress">
            <button v-for="number in 4" :key="number" type="button" @click="goToCompletedStep(number)"
              :aria-label="`Go to step ${number}: ${stepTitles[number - 1]}`"
              class="h-1.5 rounded-full transition"
              :class="number <= step ? 'bg-safaricom-light' : 'bg-white/10'" />
          </div>
        </div>

        <div class="px-4 py-5 sm:px-7 sm:py-7">
          <div v-if="readOnly" class="mb-4 rounded-xl border border-purple-400/30 bg-purple-500/10 px-4 py-3 text-center text-sm font-bold text-purple-200">
            MC preview — interaction is disabled
          </div>
          <div v-if="!configReady" class="rounded-xl border border-visa-gold/30 bg-visa-gold/10 px-4 py-3 text-sm text-visa-gold">
            Match squads are being prepared. Prediction entry opens when fixture setup is complete.
          </div>
          <div v-if="loadingSaved" class="py-12 text-center text-sm text-gray-500">Loading your saved prediction…</div>

          <!-- Step 1: large, thumb-friendly score controls. -->
          <section v-else-if="step === 1" class="prediction-step" aria-labelledby="score-title">
            <div class="mb-5 text-center">
              <h3 id="score-title" class="text-lg font-black text-white sm:text-xl">What will the final score be?</h3>
              <p class="mt-1 text-xs text-gray-500">Use + and − or tap a common score below.</p>
            </div>
            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 sm:gap-5">
              <ScoreStepper v-model="form.score_home" :team="match.home_team" :flag="teamFlag(match.home_team)" />
              <span class="pt-9 text-2xl font-black text-gray-600 sm:text-3xl">–</span>
              <ScoreStepper v-model="form.score_away" :team="match.away_team" :flag="teamFlag(match.away_team)" />
            </div>
            <div class="mt-5">
              <p class="mb-2 text-center text-[11px] font-bold uppercase tracking-widest text-gray-600">Quick picks</p>
              <div class="grid grid-cols-4 gap-2">
                <button v-for="score in quickScores" :key="score.join('-')" type="button" @click="setScore(score)"
                  class="min-h-11 rounded-xl border text-sm font-black transition active:scale-95"
                  :class="isScore(score) ? 'border-safaricom-light bg-safaricom/20 text-white' : 'border-white/10 bg-white/5 text-gray-400 hover:border-white/25'">
                  {{ score[0] }}–{{ score[1] }}
                </button>
              </div>
            </div>
          </section>

          <!-- Step 2 -->
          <section v-else-if="step === 2" class="prediction-step" aria-labelledby="scorer-title">
            <div class="mb-4 text-center">
              <h3 id="scorer-title" class="text-lg font-black text-white sm:text-xl">Who scores first?</h3>
              <p class="mt-1 text-xs text-gray-500">Search or tap a player card.</p>
            </div>
            <PlayerCardPicker v-model="form.first_scorer" label="First goalscorer" :groups="playerGroups"
              fallback-value="No goal / N/A" fallback-label="No goal in the match" />
          </section>

          <!-- Step 3 -->
          <section v-else-if="step === 3" class="prediction-step" aria-labelledby="potm-title">
            <div class="mb-4 text-center">
              <h3 id="potm-title" class="text-lg font-black text-white sm:text-xl">Who will be Player of the Match?</h3>
              <p class="mt-1 text-xs text-gray-500">Choose the player you expect to stand out.</p>
            </div>
            <PlayerCardPicker v-model="form.potm" label="Player of the match" :groups="playerGroups"
              fallback-value="TBD" fallback-label="Skip — no Player of the Match pick (0 pts)" />
          </section>

          <!-- Step 4: explicit review prevents accidental submissions. -->
          <section v-else class="prediction-step" aria-labelledby="review-title">
            <div class="mb-4 text-center">
              <h3 id="review-title" class="text-lg font-black text-white sm:text-xl">Review your prediction</h3>
              <p class="mt-1 text-xs text-gray-500">Check everything before saving.</p>
            </div>
            <div class="space-y-2.5">
              <button type="button" @click="step = 1" class="review-row">
                <span><span class="review-label">Final score</span><strong class="review-value">{{ form.score_home }} – {{ form.score_away }}</strong></span>
                <span class="text-xs font-bold text-safaricom-light">Edit</span>
              </button>
              <button type="button" @click="step = 2" class="review-row">
                <span class="min-w-0"><span class="review-label">First goalscorer</span><strong class="review-value truncate">{{ form.first_scorer }}</strong></span>
                <span class="text-xs font-bold text-safaricom-light">Edit</span>
              </button>
              <button type="button" @click="step = 3" class="review-row">
                <span class="min-w-0"><span class="review-label">Player of the Match</span><strong class="review-value truncate">{{ form.potm }}</strong></span>
                <span class="text-xs font-bold text-safaricom-light">Edit</span>
              </button>
            </div>
            <div v-if="hasSavedPrediction" class="mt-4 rounded-xl border border-safaricom/20 bg-safaricom/10 px-4 py-3 text-xs text-gray-300">
              This will update your previously saved prediction.
            </div>
          </section>

          <p v-if="errorMsg" role="alert" class="mt-4 rounded-xl border border-mpesa/30 bg-mpesa/10 px-3 py-2 text-center text-sm text-red-300">{{ errorMsg }}</p>
        </div>

        <footer v-if="!loadingSaved && configReady" class="sticky bottom-0 flex gap-2 border-t border-white/10 bg-[#091c12]/95 px-4 py-3 backdrop-blur sm:px-7 sm:py-4">
          <button v-if="step > 1" type="button" @click="step--" :disabled="submitting"
            class="min-h-12 rounded-xl border border-white/15 px-5 font-bold text-gray-300 transition hover:border-white/30 disabled:opacity-50">
            Back
          </button>
          <button v-if="step < 4" type="button" @click="nextStep" :disabled="readOnly"
            class="min-h-12 flex-1 rounded-xl bg-safaricom px-5 font-black text-white transition hover:bg-safaricom-dark disabled:opacity-50">
            Continue →
          </button>
          <button v-else type="submit" :disabled="submitting || readOnly"
            class="min-h-12 flex-1 rounded-xl bg-visa px-5 font-black text-white transition hover:bg-visa/80 disabled:opacity-50">
            {{ readOnly ? 'Preview only' : submitting ? 'Saving…' : hasSavedPrediction ? 'Update prediction' : 'Lock in prediction' }}
          </button>
        </footer>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import PlayerCardPicker from './PlayerCardPicker.vue'
import ScoreStepper from './ScoreStepper.vue'

const props = defineProps({
  playerId: { type: [String, Number], required: true },
  match: { type: Object, required: true },
  readOnly: { type: Boolean, default: false },
})
const emit = defineEmits(['submitted'])

const step = ref(1)
const stepTitles = ['Score', 'First scorer', 'Player of the Match', 'Review']
const quickScores = [[0, 0], [1, 0], [1, 1], [2, 0], [2, 1], [2, 2], [3, 1], [3, 2]]
const scorerOptions = computed(() => [...(props.match.home_squad ?? []), ...(props.match.away_squad ?? [])])
const playerGroups = computed(() => [
  { name: props.match.home_team, flag: teamFlag(props.match.home_team), players: props.match.home_squad ?? [] },
  { name: props.match.away_team, flag: teamFlag(props.match.away_team), players: props.match.away_squad ?? [] },
].filter(group => group.players.length))
const configReady = computed(() => scorerOptions.value.length > 0)

const form = reactive({ score_home: 0, score_away: 0, first_scorer: '', potm: '' })
const submitting = ref(false)
const loadingSaved = ref(false)
const hasSavedPrediction = ref(false)
const errorMsg = ref('')

onMounted(loadSavedPrediction)

async function loadSavedPrediction() {
  if (props.readOnly || !props.playerId) return
  loadingSaved.value = true
  try {
    const { data } = await axios.get('/api/predictions/current', { params: { player_id: props.playerId } })
    if (data.prediction) {
      Object.assign(form, {
        score_home: data.prediction.score_home,
        score_away: data.prediction.score_away,
        first_scorer: data.prediction.first_scorer,
        potm: data.prediction.potm,
      })
      hasSavedPrediction.value = true
    }
  } catch (e) {
    errorMsg.value = e.response?.data?.message ?? 'Could not load your saved prediction.'
  } finally {
    loadingSaved.value = false
  }
}

function nextStep() {
  errorMsg.value = ''
  if (step.value === 2 && !form.first_scorer) return errorMsg.value = 'Choose a first goalscorer or select “No goal”.'
  if (step.value === 3 && !form.potm) return errorMsg.value = 'Choose a Player of the Match or select “Decide later”.'
  step.value = Math.min(4, step.value + 1)
}
function goToCompletedStep(number) { if (number <= step.value) step.value = number }
function setScore([home, away]) { form.score_home = home; form.score_away = away }
function isScore([home, away]) { return form.score_home === home && form.score_away === away }

function teamFlag(team) {
  return ({ england: '/images/flags/england.svg', argentina: '/images/flags/argentina.svg' })[String(team ?? '').trim().toLowerCase()] ?? ''
}

async function submit() {
  if (props.readOnly || submitting.value) return
  if (!form.first_scorer || !form.potm) { step.value = !form.first_scorer ? 2 : 3; errorMsg.value = 'Complete this selection first.'; return }
  submitting.value = true; errorMsg.value = ''
  try {
    await axios.post('/api/predictions', { player_id: props.playerId, ...form })
    sessionStorage.setItem('prediction_submitted', '1')
    sessionStorage.setItem('last_prediction', JSON.stringify(form))
    emit('submitted')
  } catch (e) {
    errorMsg.value = e.response?.data?.message ?? 'Could not save predictions. Check your connection and try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.prediction-step { animation: prediction-step-in .25s ease both; }
.review-row { display:flex; width:100%; min-height:4rem; align-items:center; justify-content:space-between; gap:1rem; border:1px solid rgba(255,255,255,.1); border-radius:.9rem; background:rgba(255,255,255,.04); padding:.75rem 1rem; text-align:left; }
.review-label { display:block; color:#6b7280; font-size:.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
.review-value { display:block; margin-top:.15rem; color:#fff; font-size:1rem; }
@keyframes prediction-step-in { from { opacity:0; transform:translateX(10px); } to { opacity:1; transform:none; } }
@media (prefers-reduced-motion: reduce) { .prediction-step { animation:none; } }
</style>

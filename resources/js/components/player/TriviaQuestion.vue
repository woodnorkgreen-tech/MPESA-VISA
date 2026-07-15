<template>
  <div class="flex-1 flex flex-col px-4 sm:px-8 pt-4 sm:pt-6 pb-safe">

    <!-- Top bar: double-points badge + countdown ring -->
    <div class="flex items-center justify-between mb-4 sm:mb-6">
      <div>
        <span v-if="round.total" class="block text-[11px] sm:text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">
          Round {{ round.current }} of {{ round.total }}
        </span>
        <span v-if="question.is_double_points"
          class="bg-visa-gold text-black text-xs sm:text-sm font-bold px-3 py-1 rounded-full uppercase tracking-wide animate-pulse">
          2× POINTS
        </span>
        <span v-else class="text-gray-600 text-xs sm:text-sm">Standard question</span>
      </div>

      <!-- Countdown ring — scales with screen -->
      <div class="relative w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20">
        <svg class="w-full h-full -rotate-90" viewBox="0 0 56 56">
          <circle cx="28" cy="28" r="24" fill="none" stroke="#374151" stroke-width="4" />
          <circle cx="28" cy="28" r="24" fill="none"
            :stroke="timerColor"
            stroke-width="4"
            stroke-linecap="round"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="dashOffset"
            style="transition: stroke-dashoffset 1s linear" />
        </svg>
        <span class="absolute inset-0 flex items-center justify-center font-black text-lg sm:text-xl md:text-2xl"
          :class="timeLeft <= 5 ? 'text-mpesa animate-pulse' : 'text-white'">
          {{ timeLeft }}
        </span>
      </div>
    </div>

    <!-- Question text — grows to fill available space, vertically centred -->
    <div class="flex-1 flex items-center justify-center py-2 sm:py-4">
      <p class="text-xl sm:text-2xl md:text-3xl font-semibold text-center leading-relaxed max-w-2xl">
        {{ question.text }}
      </p>
    </div>

    <!-- Answer options -->
    <div class="grid gap-3 sm:gap-4 mt-4 mb-4 sm:mb-6"
      :class="question.options.length === 2 ? 'grid-cols-1 max-w-md mx-auto w-full' : 'grid-cols-2'">
      <button
        v-for="(option, idx) in question.options"
        :key="idx"
        :disabled="submitting || timeLeft === 0 || readOnly"
        @click="selectAnswer(option)"
        :class="optionClass(option)"
        class="relative w-full py-4 sm:py-5 px-4 sm:px-5 rounded-2xl font-semibold text-sm sm:text-base transition-all duration-200 text-left border-2 disabled:cursor-default active:scale-95">
        <span class="inline-flex items-center justify-center w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-white/10 text-xs font-black mr-2 flex-shrink-0">
          {{ labels[idx] }}
        </span>
        {{ option }}
      </button>
    </div>

    <!-- Feedback banner -->
    <div v-if="answered || timeLeft === 0" class="text-center pb-2 sm:pb-4">
      <p v-if="submissionError" class="text-visa-gold font-bold text-base sm:text-lg">
        {{ submissionError }}
      </p>
      <p v-else-if="answered && timeLeft > 0" class="text-safaricom-light font-bold text-base sm:text-lg">
        ✓ Answer saved — tap another choice to change it ({{ timeLeft }}s left)
      </p>
      <p v-else class="text-gray-500 text-sm sm:text-base">Time's up!</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  question: { type: Object, required: true },
  round: { type: Object, default: () => ({ current: 0, total: 0 }) },
  playerId: { type: [String, Number], required: true },
  readOnly: { type: Boolean, default: false },
})

const emit = defineEmits(['answered'])

const labels        = ['A', 'B', 'C', 'D']
const answered      = ref(false)
const selected      = ref(null)
const isCorrect     = ref(false)
const pointsAwarded = ref(0)
const submissionError = ref('')
const submitting = ref(false)

const timeLeft      = ref(props.question.seconds_remaining ?? props.question.duration_seconds)
const circumference = 2 * Math.PI * 24

const dashOffset = computed(() =>
  circumference * (1 - timeLeft.value / props.question.duration_seconds)
)
const timerColor = computed(() => {
  const ratio = timeLeft.value / props.question.duration_seconds
  if (ratio > 0.5) return '#00C65A'
  if (ratio > 0.25) return '#F7B600'
  return '#C8102E'
})

const startedAt = Date.now()
let timer = null

onMounted(() => {
  timer = setInterval(() => {
    if (timeLeft.value > 0) timeLeft.value--
  }, 1000)
})

onUnmounted(() => clearInterval(timer))

watch(
  () => [props.question.seconds_remaining, props.question.duration_seconds],
  ([remaining], [previousRemaining, previousDuration]) => {
    if (props.question.duration_seconds !== previousDuration || Math.abs((remaining ?? 0) - timeLeft.value) > 2) {
      timeLeft.value = remaining ?? props.question.duration_seconds
    }
  },
)

function optionClass(option) {
  if (option === selected.value) {
    return 'bg-safaricom/25 border-safaricom-light text-white ring-2 ring-safaricom-light/30'
  }
  return 'bg-gray-800 border-gray-700 hover:bg-gray-700 hover:border-gray-500 text-white'
}

async function selectAnswer(option) {
  if (props.readOnly || submitting.value || timeLeft.value === 0) return
  submitting.value = true
  selected.value = option
  submissionError.value = ''

  const responseMs = Date.now() - startedAt

  try {
    const { data } = await axios.post('/api/answers', {
      player_id:        props.playerId,
      question_id:      props.question.id,
      selected_option:  option,
      response_time_ms: responseMs,
    })
    answered.value = true
    isCorrect.value     = data.is_correct
    pointsAwarded.value = data.points_awarded
    emit('answered', {
      isCorrect:     data.is_correct,
      pointsAwarded: data.points_awarded,
      totalScore:    data.total_score,
    })
  } catch (e) {
    submissionError.value = e.response?.data?.message ?? 'We could not record that answer. Please check your connection.'
  } finally {
    submitting.value = false
  }
}
</script>

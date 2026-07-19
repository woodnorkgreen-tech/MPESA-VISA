import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

/**
 * Keeps the shared event state current. Polling remains the reliable baseline;
 * visibility and connectivity events force an immediate catch-up refresh.
 * All three client types (player, admin, screen) use this same composable.
 */
export function useEventState(intervalMs = 1500) {
    const phase               = ref('lobby')
    const question            = ref(null)
    const leaderboard         = ref([])
    const leaderboards        = ref({ trivia: [], fifa: [], visa: [], prediction: [] })
    const playerCount         = ref(0)
    const predictionCount     = ref(0)
    const recentPredictions   = ref([])
    const match               = ref({ home_team: 'Home Team', away_team: 'Away Team', home_squad: [], away_squad: [] })
    const round               = ref({ current: 0, total: 0, completed: 0 })
    const activeRound         = ref({ number: 1, label: 'Football 101', status: 'coming' })
    const rounds              = ref({})
    const lastUpdatedAt       = ref(null)
    const loading             = ref(true)
    const error               = ref(null)

    let timer = null
    let requestInFlight = false
    let consecutiveFailures = 0
    const FAILURE_THRESHOLD = 3 // a single dropped poll shouldn't flash the banner

    async function fetchState() {
        if (requestInFlight) return
        requestInFlight = true
        try {
            const { data } = await axios.get('/api/state')
            phase.value             = data.phase
            question.value          = data.question
            leaderboard.value       = data.leaderboard
            leaderboards.value      = data.leaderboards ?? leaderboards.value
            playerCount.value       = data.player_count
            predictionCount.value   = data.prediction_count
            recentPredictions.value = data.recent_predictions ?? []
            match.value             = data.match ?? match.value
            round.value             = data.round ?? round.value
            activeRound.value       = data.active_round ?? activeRound.value
            rounds.value            = data.rounds ?? rounds.value
            lastUpdatedAt.value     = new Date()
            consecutiveFailures     = 0
            error.value             = null
        } catch (e) {
            consecutiveFailures += 1
            if (consecutiveFailures >= FAILURE_THRESHOLD) {
                error.value = 'Connection issue — retrying…'
            }
        } finally {
            loading.value = false
            requestInFlight = false
        }
    }

    function refreshWhenActive() {
        if (document.visibilityState === 'visible') fetchState()
    }

    onMounted(() => {
        fetchState()
        timer = setInterval(fetchState, intervalMs)
        document.addEventListener('visibilitychange', refreshWhenActive)
        window.addEventListener('online', fetchState)
    })

    onUnmounted(() => {
        clearInterval(timer)
        document.removeEventListener('visibilitychange', refreshWhenActive)
        window.removeEventListener('online', fetchState)
    })

    return { phase, question, leaderboard, leaderboards, playerCount, predictionCount, recentPredictions, match, round, activeRound, rounds, lastUpdatedAt, loading, error, fetchState }
}

import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { getQuizMeta } from "../../quiz/services/quizService";
import { API_BASE } from "../../../config/api";
import type { QuizMeta } from "../../../features/quiz/types/quiz";
import "../styles/hostQuiz.css";

type Player = {
  id: number;
  name: string;
  score: number;
};

type Question = {
  id: string;
  text: string;
  type: string;
  answers?: { id: string; text: string }[];
};

export default function HostQuiz() {
  const navigate = useNavigate();
  const { slug } = useParams<{ slug: string }>();

  const colors = ["#e21b3c", "#1368ce", "#d89e00", "#26890c"];

  const [meta, setMeta] = useState<QuizMeta | null>(null);
  const [gamePin, setGamePin] = useState<string | null>(null);
  const [players, setPlayers] = useState<Player[]>([]);
  const [state, setState] = useState<string>("lobby");
  const [question, setQuestion] = useState<Question | null>(null);
  const [answersCount, setAnswersCount] = useState(0);

  if (!slug) return <div>Invalid quiz</div>;

  // =========================
  // 🔥 LOAD PIN FROM STORAGE
  // =========================
  useEffect(() => {
    const savedPin = localStorage.getItem(`host_pin_${slug}`);
    if (savedPin) {
      setGamePin(savedPin);
    }
  }, [slug]);

  // =========================
  // META
  // =========================
  useEffect(() => {
    getQuizMeta(slug).then(setMeta).catch(console.error);
  }, [slug]);

  // =========================
  // CREATE GAME
  // =========================
  const handleCreateGame = async () => {
    const res = await fetch(`${API_BASE}/live-quiz/create-game.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ quiz_id: slug }),
    });

    const data = await res.json();

    setGamePin(data.pin);

    // 🔥 SAVE
    localStorage.setItem(`host_pin_${slug}`, data.pin);
  };

  // =========================
  // POLLING
  // =========================
  useEffect(() => {
    if (!gamePin) return;

    const interval = setInterval(async () => {
      try {
        const res = await fetch(
          `${API_BASE}/live-quiz/get-game-state.php?pin=${gamePin}`
        );

        // 🔥 ha game törölve / invalid → reset
        if (!res.ok) {
          localStorage.removeItem(`host_pin_${slug}`);
          setGamePin(null);
          return;
        }

        const data = await res.json();

        setPlayers(data.players || []);
        setState(data.game?.state || "lobby");
        setQuestion(data.question || null);
        setAnswersCount(data.answers_count || 0);
      } catch (err) {
        console.error("Polling error:", err);
      }
    }, 2000);

    return () => clearInterval(interval);
  }, [gamePin, slug]);

  const handleExit = () => {
    localStorage.removeItem(`host_pin_${slug}`);
    navigate(`/quiz/${slug}`);
  };

  // =========================
  // START
  // =========================
  const handleStartGame = async () => {
    await fetch(`${API_BASE}/live-quiz/start-game.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ pin: gamePin }),
    });
  };

  // =========================
  // NEXT
  // =========================
  const handleNextQuestion = async () => {
    await fetch(`${API_BASE}/live-quiz/next-question.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ pin: gamePin }),
    });
  };

  return (
    <div className="host-container">

      {/* TOP BAR */}
      {state !== "lobby" && gamePin && (
        <div className="host-topbar">
          <div className="exit-box">
            <button onClick={handleExit}>Exit</button>
          </div>

          <div className="pin-box">PIN: {gamePin}</div>
        </div>
      )}

      {/* NO GAME */}
      {!gamePin && (
        <div className="before-host">
          <h1>{meta?.title}</h1>
          <button className="start-btn" onClick={handleCreateGame}>
            Start Host
          </button>
        </div>
      )}

      {/* LOBBY */}
      {gamePin && state === "lobby" && (
        
        <div className="lobby">
                  <div className="host-topbar">

          <div className="exit-box">
            <button onClick={handleExit}>Exit</button>
          </div>
          </div>
          <h1>{meta?.title}</h1>
          <h2>Pin:</h2>
          <div className="pin-big">{gamePin}</div>

          <h2>Players</h2>
          {players.length === 0 && <p>No players yet...</p>}

          {players.map((p) => (
            <div key={p.id}>{p.name}</div>
          ))}

          <button className="start-btn" onClick={handleStartGame}>
            Start Game
          </button>
        </div>
      )}

      {/* PLAYING */}
      {state === "playing" && question && (
        <div className="question-screen">

          <h1 className="question-text">{question.text}</h1>

          {/* ANSWER PROGRESS */}
          <div style={{ marginBottom: 20, fontSize: "1.2rem" }}>
            Answers: {answersCount} / {players.length}
          </div>

          <div className="answers-grid">
            {question.answers?.map((a, index) => (
              <div
                key={a.id}
                className="answer-box"
                style={{
                  backgroundColor: colors[index % colors.length],
                }}
              >
                {a.text}
              </div>
            ))}
          </div>

          {/* AUTO NEXT */}
          {answersCount === players.length && players.length > 0 && (
            <button className="next-btn" onClick={handleNextQuestion}>
              Everyone answered → Next
            </button>
          )}

          {/* MANUAL NEXT */}
          {answersCount !== players.length && (
            <button className="next-btn" onClick={handleNextQuestion}>
              Next →
            </button>
          )}
        </div>
      )}

      {/* RESULTS */}
      {state === "finished" && (
        <div className="results">
          <h1>🏆 Results</h1>

          {[...players]
            .sort((a, b) => b.score - a.score)
            .map((p, i) => (
              <div key={p.id} className="result-row">
                {i + 1}. {p.name} - {p.score}
              </div>
            ))}
        </div>
      )}
    </div>
  );
}
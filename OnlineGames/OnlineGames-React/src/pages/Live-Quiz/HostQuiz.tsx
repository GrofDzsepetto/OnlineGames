import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { getQuizMeta } from "../../services/quizService";
import { API_BASE } from "../../config/api";
import type { QuizMeta } from "../../types/quiz";

type Player = {
  id: number;
  name: string;
  score: number;
};

export default function HostQuiz() {
  const { slug } = useParams<{ slug: string }>();
  const [meta, setMeta] = useState<QuizMeta | null>(null);
  const [gamePin, setGamePin] = useState<string | null>(null);
  const [players, setPlayers] = useState<Player[]>([]);
  const [state, setState] = useState<string>("lobby");

  if (!slug) {
    return <div>Invalid quiz</div>;
  }

  async function load(slug: string) {
    const data = await getQuizMeta(slug);
    setMeta(data);
  }
    

const handleCreateGame = async () => {
  try {
    const res = await fetch(`${API_BASE}/live-quiz/create-game.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        quiz_id: slug,
      }),
    });

    if (!res.ok) {
      const text = await res.text();
      console.error("API error:", text);
      return;
    }
    console.log(res)
    const data = await res.json();

    if (!data.ok) {
      console.error("Backend error:", data);
      return;
    }

    setGamePin(data.pin);

  } catch (err) {
    console.error(err);
  }
};

useEffect(() => {
  if (!slug) return;

  load(slug);
}, [slug]);
  // POLLING
  useEffect(() => {
    if (!gamePin) return;

    const interval = setInterval(async () => {
      try {
        const res = await fetch(
          `${API_BASE}/live-quiz/get-game-state.php?pin=${gamePin}`
        );
        const data = await res.json();

        setPlayers(data.players || []);
        setState(data.game?.state || "lobby");
      } catch (err) {
        console.error(err);
      }
    }, 2000);

    return () => clearInterval(interval);
  }, [gamePin]);

  // START GAME
  const handleStartGame = async () => {
    try {
      await fetch(`${API_BASE}/live-quiz/start-game.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ pin: gamePin }),
      });
    } catch (err) {
      console.error(err);
    }
  };

  return (
    <div style={{ textAlign: "center", marginTop: 50 }}>
      <h1>Host Quiz</h1>

      {/* 🔥 melyik quiz */}
      <p style={{ opacity: 0.7 }}>
        Hosting quiz: <b>{meta?.title ?? "Loading..."}</b>
      </p>

      {!gamePin ? (
        <button onClick={handleCreateGame}>
          Start Quiz
        </button>
      ) : (
        <div>
          <h2>Game PIN:</h2>
          <h1 style={{ fontSize: "3rem" }}>{gamePin}</h1>

          <h3>Players:</h3>
          {players.length === 0 && <p>No players yet...</p>}

          {players.map((p) => (
            <div key={p.id}>{p.name}</div>
          ))}

          {state === "lobby" && (
            <button onClick={handleStartGame}>
              Start Game
            </button>
          )}
        </div>
      )}
    </div>
  );
}
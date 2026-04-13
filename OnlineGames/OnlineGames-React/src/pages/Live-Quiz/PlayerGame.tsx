import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { API_BASE } from "../../config/api";
import LobbyView from "./Components/LobbyView";
import QuestionView from "./Components/QuestionView";
import ResultView from "./Components/ResultView";

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

export default function PlayerGame() {
  const { pin, playerId } = useParams<{
    pin: string;
    playerId: string;
  }>();

  const [state, setState] = useState<"lobby" | "playing" | "finished">("lobby");
  const [players, setPlayers] = useState<Player[]>([]);
  const [question, setQuestion] = useState<Question | null>(null);

  // 🔁 POLLING (EZ AZ AGY)
  useEffect(() => {
    if (!pin) return;

    const interval = setInterval(async () => {
      try {
        const res = await fetch(
          `${API_BASE}/live-quiz/get-game-state.php?pin=${pin}`
        );

        const data = await res.json();

        setState(data.game?.state || "lobby");
        setPlayers(data.players || []);
        setQuestion(data.question || null);

      } catch (err) {
        console.error(err);
      }
    }, 2000);

    return () => clearInterval(interval);
  }, [pin]);

  // 🧠 ANSWER
  const handleAnswer = async (answerId: string) => {
    try {
      await fetch(`${API_BASE}/live-quiz/submit-answer.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          pin,
          player_id: playerId,
          answer_id: answerId,
        }),
      });
    } catch (err) {
      console.error(err);
    }
  };

  // 🎮 RENDER STATE ALAPJÁN

  if (state === "lobby") {
    return <LobbyView players={players} pin={pin!} />;
  }

  if (state === "playing") {
    return <QuestionView question={question} onAnswer={handleAnswer} />;
  }

  if (state === "finished") {
    return <ResultView players={players} />;
  }

  return <div>Unknown state...</div>;
}
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { API_BASE } from "../../config/api";

export default function JoinGame() {
  const [pin, setPin] = useState("");
  const [name, setName] = useState("");
  const navigate = useNavigate();

  const handleJoin = async () => {
    try {
      const res = await fetch(`${API_BASE}/live-quiz/join-game.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ pin, name }),
      });

      const data = await res.json();

      if (!data.ok) {
        alert("Join failed");
        return;
      }

      // 👉 ide navigálunk
      navigate(`/play/${pin}/${data.player_id}`);

    } catch (err) {
      console.error(err);
    }
  };

  return (
    <div style={{ textAlign: "center", marginTop: 50 }}>
      <h1>Join Game</h1>

      <input
        placeholder="Game PIN"
        value={pin}
        onChange={(e) => setPin(e.target.value)}
      />

      <input
        placeholder="Your name"
        value={name}
        onChange={(e) => setName(e.target.value)}
      />

      <button onClick={handleJoin}>Join</button>
    </div>
  );
}
export default function LobbyView({ players, pin }: any) {
  return (
    <div className="player-lobby">
      <h1>Waiting for host...</h1>
      <h2>PIN: {pin}</h2>

      <div className="player-list">
        {players.length === 0 && <p>No players yet...</p>}

        {players.map((p: any) => (
          <div key={p.id}>{p.name}</div>
        ))}
      </div>
    </div>
  );
}
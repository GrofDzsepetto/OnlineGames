export default function LobbyView({ players, pin }: any) {
  return (
    <div style={{ textAlign: "center", marginTop: 50 }}>
      <h1>Waiting for host...</h1>
      <h2>PIN: {pin}</h2>

      <h3>Players:</h3>

      {players.length === 0 && <p>No players yet...</p>}

      {players.map((p: any) => (
        <div key={p.id}>{p.name}</div>
      ))}
    </div>
  );
}
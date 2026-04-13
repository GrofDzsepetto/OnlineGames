export default function ResultView({ players }: any) {
  return (
    <div style={{ textAlign: "center", marginTop: 50 }}>
      <h1>Results</h1>

      {players
        .sort((a: any, b: any) => b.score - a.score)
        .map((p: any) => (
          <div key={p.id}>
            {p.name} - {p.score}
          </div>
        ))}
    </div>
  );
}
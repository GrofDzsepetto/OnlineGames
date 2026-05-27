export default function ResultView({ players }: any) {
  return (
    <div className="player-results">
      <h1>🏆 Results</h1>

      {[...players]
        .sort((a: any, b: any) => b.score - a.score)
        .map((p: any, i: number) => (
          <div key={p.id}>
            {i + 1}. {p.name} - {p.score}
          </div>
        ))}
    </div>
  );
}
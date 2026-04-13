export default function QuestionView({ question, onAnswer }: any) {
  if (!question) return <div>Loading question...</div>;

  return (
    <div style={{ textAlign: "center", marginTop: 50 }}>
      <h2>{question.text}</h2>

      {question.answers?.map((a: any) => (
        <button
          key={a.id}
          onClick={() => onAnswer(a.id)}
          style={{ display: "block", margin: 10 }}
        >
          {a.text}
        </button>
      ))}
    </div>
  );
}
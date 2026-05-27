import { useEffect, useRef, useState } from "react";
import "../styles/questionView.css";

const colors = ["#e21b3c", "#1368ce", "#d89e00", "#26890c"];

export default function QuestionView({ question, onAnswer }: any) {
  const [hasAnswered, setHasAnswered] = useState(false);
  const [time, setTime] = useState(0);

  const intervalRef = useRef<any>(null); // ⬅️ EZ A KULCS

  // 🔁 új kérdés
  useEffect(() => {
    setHasAnswered(false);
    setTime(0);

    intervalRef.current = setInterval(() => {
      setTime((t) => t + 1);
    }, 1000);

    return () => clearInterval(intervalRef.current);
  }, [question?.id]);

  const handleClick = (answerId: string) => {
    if (hasAnswered) return;

    clearInterval(intervalRef.current); // ⬅️ ITT ÁLLÍTOD MEG

    onAnswer(answerId, time);
    setHasAnswered(true);
  };

  if (!question) return <div className="question-loading">Loading...</div>;

  return (
    <div className="question-container">
      <div className="question-timer">{time}s</div>

      {question.answers?.map((a: any, index: number) => (
        <button
          key={a.id}
          onClick={() => handleClick(a.id)}
          disabled={hasAnswered}
          className="answer-btn"
          style={{
            backgroundColor: colors[index % colors.length],
          }}
        />
      ))}
    </div>
  );
}
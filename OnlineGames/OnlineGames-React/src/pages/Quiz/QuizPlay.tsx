import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { getQuizQuestions } from "../../services/quizService";
import type { QuizQuestion } from "../../types/quiz";
import QuizQuestionComponent from "../../components/quiz/QuizQuestion";
import QuizMatchingQuestion from "../../components/quiz/QuizMatchingQuestion";
import QuizResult from "../../components/quiz/QuizResult";

const QuizPlay = () => {
  const [questions, setQuestions] = useState<QuizQuestion[]>([]);
  const [current, setCurrent] = useState(0);
  const [score, setScore] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const { slug } = useParams();


useEffect(() => {
  if (!slug) {
    setLoading(false);
    setError("Missing quiz slug in route params");
    return;
  }

  setLoading(true);
  setError(null);

  getQuizQuestions(slug)
    .then((data) => {
      setQuestions(data);
      setCurrent(0);
      setScore(0);
    })
    .catch((err) => {
      setError(err?.message ?? "Failed to load quiz");
      setQuestions([]);
    })
    .finally(() => setLoading(false));
}, [slug]);


  if (loading) return <div>Loading...</div>;
  if (error) return <div>{error}</div>;
  if (questions.length === 0) return <div>No questions found for this quiz.</div>;

 if (current >= questions.length) {
  return (
    <QuizResult
      score={score}
      total={questions.length}
      quizId={slug!}
    />
  );
}


  const q = questions[current];

  const handleAnswered = (correct: boolean) => {
    if (correct) setScore((s) => s + 1);
    setCurrent((c) => c + 1);
  };
  console.log("PLAY PARAM:", slug);
  return (
    
    <div className="quiz-play">
      {/* fejléc: bal felül progress */}
      <div className="quiz-play-header">
        <div className="quiz-progress">
          Kérdés: {current + 1} / {questions.length}
        </div>

        {/* opcionális: jobb felül pontszám */}
        <div className="quiz-score">
          Pont: {score}
        </div>
      </div>

      {/* kérdés UI */}
      {q.type === "MATCHING" ? (
        <QuizMatchingQuestion question={q} onAnswer={handleAnswered} />
      ) : (
        <QuizQuestionComponent question={q} onAnswer={handleAnswered} />
      )}
    </div>
  );
};

export default QuizPlay;

import type { Quiz } from "../../types/quiz";
import { useNavigate } from "react-router-dom";

const QuizCard = ({ quiz }: { quiz: Quiz }) => {
  const navigate = useNavigate();

  return (
    <div
      className="quiz-card"
      role="button"
      tabIndex={0}
      onClick={() => navigate(`/quiz/${quiz.id}`)}
      onKeyDown={(e) => {
        if (e.key === "Enter") {
          navigate(`/quiz/${quiz.id}`);
        }
      }}
    >
      <h3>{quiz.title}</h3>
      <p>{quiz.description}</p>
    </div>
  );
};

export default QuizCard;

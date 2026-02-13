import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { getQuizzes, deleteQuiz } from "../../services/quizService";
import type { Quiz } from "../../types/quiz";
import QuizCard from "../../components/quiz/QuizCard";
import { useAuth } from "../../auth/AuthContext";
import "../../styles/Quiz/QuizList.css";

const QuizList = () => {
  const navigate = useNavigate();
  const { user } = useAuth();

  const [quizzes, setQuizzes] = useState<Quiz[]>([]);
  const [deletingId, setDeletingId] = useState<string | null>(null);
  const [selectedLanguage, setSelectedLanguage] = useState<string>("all");

  const load = async () => {
    try {
      const data: Quiz[] = await getQuizzes();
      setQuizzes(data ?? []);
    } catch (err) {
      console.error("getQuizzes error:", err);
      setQuizzes([]);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const filteredQuizzes =
    selectedLanguage === "all"
      ? quizzes
      : quizzes.filter(
          (q) =>
            q.language?.toLowerCase() ===
            selectedLanguage.toLowerCase()
        );

  const onDelete = async (quiz: Quiz) => {
    if (!quiz.id) return;

    const ok = window.confirm(
      `Biztos törlöd a kvízt?\n\n${quiz.title}`
    );
    if (!ok) return;

    try {
      setDeletingId(quiz.id);
      await deleteQuiz(quiz.id);
      setQuizzes((prev) =>
        prev.filter((q) => q.id !== quiz.id)
      );
    } catch (e: any) {
      alert(e?.message ?? "Törlés sikertelen");
    } finally {
      setDeletingId(null);
    }
  };

  return (
    <div>
      <div className="quizlist-header">
        <div className="quizlist-left">
          <select
            className="quiz-language-select"
            value={selectedLanguage}
            onChange={(e) =>
              setSelectedLanguage(e.target.value)
            }
          >
            <option value="all">🌍 All</option>
            <option value="hu">🇭🇺 Magyar</option>
            <option value="en">🇬🇧 English</option>
          </select>
        </div>

        <div className="quizlist-center">
          <h2 className="quizlist-title">
            Select a Quiz
          </h2>
        </div>

        <div className="quizlist-right">
          {user && (
            <Link
              to="/create-quiz"
              className="create-quiz-btn"
            >
              + Create Quiz
            </Link>
          )}
        </div>
      </div>

      {filteredQuizzes.length > 0 ? (
        filteredQuizzes.map((quiz) => (
          <div key={quiz.id} className="quiz-wrapper">

            {/* ACTION BUTTONS */}
            <div
              style={{
                position: "absolute",
                top: "12px",
                right: "12px",
                display: "flex",
                gap: "8px",
                zIndex: 50
              }}
            >
              <button
                className="settings-btn"
                onClick={(e) => {
                  e.stopPropagation();
                  navigate(`/edit-quiz/${quiz.id}`);
                }}
              >
                ✏
              </button>

              <button
                className="delete-btn"
                disabled={deletingId === quiz.id}
                onClick={(e) => {
                  e.stopPropagation();
                  onDelete(quiz);
                }}
              >
                {deletingId === quiz.id ? "..." : "🗑"}
              </button>
            </div>

            <div
              onClick={() =>
                navigate(`/quiz/${quiz.slug}`)
              }
              style={{ cursor: "pointer" }}
            >
              <QuizCard quiz={quiz} />
            </div>

            {quiz.creator_name && (
              <span className="creator-name">
                Created by: {quiz.creator_name}
              </span>
            )}
          </div>
        ))
      ) : (
        <p>No quizzes available.</p>
      )}
    </div>
  );
};

export default QuizList;

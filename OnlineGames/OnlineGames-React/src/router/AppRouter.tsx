import { createBrowserRouter } from "react-router-dom";
import Layout from "../components/layout/Layout";
import Home from "../pages/Home";
import QuizList from "../features/quiz/pages/QuizList";
import QuizPlay from "../features/quiz/pages/QuizPlay";
import NotFound from "../pages/NotFound";
import Login from "../pages/Login";
import CreateQuiz from "../features/quiz/pages/CreateQuiz";
import EditQuiz from "../features/quiz/pages/EditQuiz";
import Help from "../pages/Help";
import QuizInfo from "../features/quiz/components/QuizInfo";
import HostQuiz from "../features/liveQuiz/pages/HostQuiz";
import JoinGame from "../features/liveQuiz/pages/JoinGame";
import PlayerGame from "../features/liveQuiz/pages/PlayerGame";

export const router = createBrowserRouter([
  {
    element: <Layout />,
    children: [
      { path: "/", element: <Home /> },
      { path: "/quizzes", element: <QuizList /> },
      { path: "/quiz/:slug", element: <QuizInfo /> },
      { path: "/play/:slug", element: <QuizPlay /> },

      { path: "/login", element: <Login /> },
      { path: "/create-quiz", element: <CreateQuiz /> },
      { path: "/edit-quiz/:id", element: <EditQuiz /> },
      { path: "/help", element: <Help /> },
      {path: "/host/:slug", element: <HostQuiz />},
      {path: "/join", element: <JoinGame />},
      { path: "/play/:pin/:playerId", element: <PlayerGame /> }
    ],
  },
  { path: "*", element: <NotFound /> },
]);

 
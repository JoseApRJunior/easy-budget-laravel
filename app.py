from flask import Flask, request, jsonify
from sentence_transformers import SentenceTransformer

app = Flask(__name__)
model = SentenceTransformer('all-mpnet-base-v2')

@app.route("/v1/embeddings", methods=["POST"])
def embed():
    data = request.get_json()
    texts = data.get("input", [])
    embeddings = model.encode(texts).tolist()

    response = {
        "object": "embedding",
        "model": "all-mpnet-base-v2",
        "data": [
            {
                "index": i,
                "embedding": emb
            } for i, emb in enumerate(embeddings)
        ]
    }
    return jsonify(response)

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=11434)
